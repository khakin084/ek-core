<?php

namespace App\Contracts;

/**
 * The read-side permission contract.
 *
 * Two implementations, same signature, so CheckPermission is identical everywhere:
 *
 *   - auth service  -> App\Services\PermissionResolver        (reads the registry directly)
 *   - every other   -> App\Services\RemotePermissionResolver  (fetches + caches by pv)
 *
 * Bind in each service's AppServiceProvider:
 *
 *     $this->app->bind(ResolvesPermissions::class, PermissionResolver::class);        // auth
 *     $this->app->bind(ResolvesPermissions::class, RemotePermissionResolver::class);  // elsewhere
 */
interface ResolvesPermissions
{
    /**
     * Effective level 0..3 for a LEAF module.
     *
     * @throws \LogicException   when the key is a container (menu visibility, not access)
     * @throws \RuntimeException when the key is not registered
     */
    public function levelFor(string $tenantId, string $userId, string $moduleKey): int;

    public function can(string $tenantId, string $userId, string $moduleKey, string $min = 'read'): bool;

    /**
     * Flattened map: [ 'accounts.control_accounts' => 2, 'fleet' => 3, ... ]
     */
    public function flatten(string $tenantId, string $userId, ?int $pv = null): array;
}
