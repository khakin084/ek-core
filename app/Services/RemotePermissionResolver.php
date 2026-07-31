<?php

namespace App\Services;

use App\Contracts\ResolvesPermissions;
use App\Enums\PermissionLevel;
use App\Helpers\ServiceDiscoveryHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * The permission resolver for every service EXCEPT auth.
 *
 * Downstream services have no modules table, no roles, no overrides — they hold a cached
 * copy of the effective map and nothing else.
 *
 * HOW INVALIDATION WORKS
 *
 *   The cache key is perm:{tenant}:{user}:{pv}, and pv comes from the SIGNED TOKEN. When
 *   an admin changes rights, auth bumps permission_version; the next request still carries
 *   the old pv, so the key misses and this refetches. No eviction, no coordination, and a
 *   correct answer even if the RabbitMQ invalidation message is dropped entirely.
 *
 *   The pv is read from the request attributes set by AuthenticateJwt, which is why that
 *   middleware must run first.
 *
 * FAILURE POSTURE: FAIL CLOSED. If auth is unreachable on a cache miss, this denies rather
 * than guessing. An outage that degrades to "everyone is an admin" is far worse than one
 * that degrades to "nobody can act".
 */
class RemotePermissionResolver implements ResolvesPermissions
{
    private const TTL = 3600;   // pv makes this safe to hold; TTL is hygiene, not correctness

    public function levelFor(string $tenantId, string $userId, string $moduleKey): int
    {
        $payload = $this->payload($tenantId, $userId);

        // Containers are menu visibility only. Auth ships the container list so a route
        // mistakenly pointed at a parent fails loudly here rather than silently passing on
        // the parent's level-1 tile grant.
        if (in_array($moduleKey, $payload['containers'] ?? [], true)) {
            throw new LogicException("[{$moduleKey}] is a container — action checks must target a leaf.");
        }

        if (! array_key_exists($moduleKey, $payload['perms'] ?? [])) {
            throw new RuntimeException("Unknown module key [{$moduleKey}] — has modules:sync run in auth?");
        }

        return (int) $payload['perms'][$moduleKey];
    }

    public function can(string $tenantId, string $userId, string $moduleKey, string $min = 'read'): bool
    {
        return $this->levelFor($tenantId, $userId, $moduleKey)
            >= PermissionLevel::fromKey($min)->value;
    }

    public function flatten(string $tenantId, string $userId, ?int $pv = null): array
    {
        return $this->payload($tenantId, $userId, $pv)['perms'] ?? [];
    }

    /* ------------------------------------------------------------------ */

    private function payload(string $tenantId, string $userId, ?int $pv = null): array
    {
        $pv ??= $this->permissionVersionFromRequest();

        return Cache::remember(
            "perm:{$tenantId}:{$userId}:{$pv}",
            self::TTL,
            fn () => $this->fetch($tenantId, $userId),
        );
    }

    private function fetch(string $tenantId, string $userId): array
    {
        try {
            $response = Http::withToken($this->serviceToken())
                ->timeout(5)
                ->retry(2, 200)
                ->get(ServiceDiscoveryHelper::serviceUrl(
                    'ek-auth',
                    "/api/v1/internal/permissions/{$tenantId}/{$userId}"
                ));

            if ($response->failed()) {
                throw new RuntimeException("Auth returned {$response->status()}.");
            }

            return $response->json();

        } catch (Throwable $e) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => 'Permission fetch failed — denying by default: ' . $e->getMessage(),
                'tenant'  => $tenantId,
                'user'    => $userId,
            ]));

            // Fail closed. Returning an empty map means every check evaluates to 0/deny.
            // Do NOT cache this — a transient outage must not pin a deny for an hour.
            throw new RuntimeException('Permission service unavailable.');
        }
    }

    /**
     * pv travels in the token, so it is available on every authenticated request.
     */
    private function permissionVersionFromRequest(): int
    {
        $pv = request()?->attributes->get('permission_version');

        if ($pv === null) {
            throw new RuntimeException('No permission_version on the request — did AuthenticateJwt run?');
        }

        return (int) $pv;
    }

    /**
     * Service-to-service credential for the internal endpoint. Delegates to
     * AuthenticationService so there is ONE machine-token implementation (one client, one
     * cache key) — a second copy here is exactly what drifted onto the wrong client.
     */
    private function serviceToken(): string
    {
        return app(\App\Services\AuthenticationService::class)->serviceToken();
    }
}