<?php

namespace App\Support;

/**
 * The acting tenant for the current execution, in one place.
 *
 * In a REQUEST it falls back to the token attribute the auth middleware set — so existing
 * middleware needs no change. In a JOB, COMMAND, or SEEDER (no request) the tenant must be
 * set explicitly, because there is no token to read.
 *
 * Registered as a singleton so a value set() once is visible everywhere for that execution.
 */
class TenantContext
{
    private ?string $tenantId = null;

    private bool $explicit = false;

    /** Explicitly bind the tenant — for jobs, commands, or impersonation. */
    public function set(?string $tenantId): static
    {
        $this->tenantId = $tenantId;
        $this->explicit = true;

        return $this;
    }

    /**
     * Current tenant id. Explicit value wins; otherwise the verified token attribute set by
     * AuthenticateSession / AuthenticateJwt. Null means "no tenant in scope".
     */
    public function id(): ?string
    {
        if ($this->explicit) {
            return $this->tenantId;
        }

        return request()?->attributes->get('tenant_id');
    }

    public function has(): bool
    {
        return $this->id() !== null;
    }

    /** Run a callback scoped to a specific tenant, restoring the previous scope after. */
    public function runFor(string $tenantId, callable $callback): mixed
    {
        $prevId       = $this->tenantId;
        $prevExplicit = $this->explicit;

        $this->set($tenantId);

        try {
            return $callback();
        } finally {
            $this->tenantId = $prevId;
            $this->explicit = $prevExplicit;
        }
    }

    public function forget(): void
    {
        $this->tenantId = null;
        $this->explicit = false;
    }
}
