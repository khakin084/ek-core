<?php

use App\Contracts\ResolvesPermissions;
use App\Enums\PermissionLevel;
use App\Models\User;
use App\Services\Navigation\MenuComposer;

/**
 * Auth helpers, rewritten for tenant-scoped tokens and ordinal permissions.
 *
 * WHERE IDENTITY COMES FROM NOW
 *
 *   request()->attributes  — set by AuthenticateSession (web) or AuthenticateJwt (api)
 *                            from the SIGNED token. Never request input: input carries
 *                            user-supplied data, and identity must not share that space.
 *   session()              — the web shell's copy of user, permissions, menu, tenant.
 *
 * WHAT CHANGED
 *
 *  - `sub` is a plain uuid, not a JSON object. The old json_decode(...)['id'] returns
 *    null now, so authUserId() was silently returning null on every request.
 *  - Permissions are a { module_key => 0..3 } map, not scope strings. The `core:` prefix
 *    parsing is gone — module keys are globally unique, so nothing needs stripping.
 *  - Everything is tenant-scoped. authTenantId() is the one most queries need.
 */

if (!function_exists('authUserId')) {
    /**
     * The signed-in user's uuid.
     */
    function authUserId(): ?string
    {
        return request()?->attributes->get('user_id')
            ?? session('auth_user.id')
            ?? auth()->id();
    }
}

if (!function_exists('authTenantId')) {
    /**
     * The tenant this request is scoped to. Comes from the token's `tid` claim, so it
     * cannot be spoofed by a header or query parameter.
     *
     * Use this on every tenant-owned query and every insert.
     */
    function authTenantId(): ?string
    {
        return request()?->attributes->get('tenant_id')
            ?? session('tenant.id');
    }
}

if (!function_exists('authTenant')) {
    /**
     * @return array{id:string,name:string,slug:string}|null
     */
    function authTenant(): ?array
    {
        return session('tenant');
    }
}

if (!function_exists('authPermissionVersion')) {
    /**
     * The token's pv claim — the cache key component that makes permission changes take
     * effect without re-issuing tokens.
     */
    function authPermissionVersion(): ?int
    {
        $pv = request()?->attributes->get('permission_version');

        return $pv === null ? null : (int) $pv;
    }
}

if (!function_exists('authUser')) {
    /**
     * The signed-in user. Non-persisted — ek-core no longer owns the users table.
     */
    function authUser(): User|\Illuminate\Contracts\Auth\Authenticatable|null
    {
        if ($user = auth()->user()) {
            return $user;
        }

        $session = session('auth_user');

        if (is_array($session) && !empty($session['id'])) {
            // permissions and menu are held separately — they are not model attributes.
            return (new User())->forceFill(
                collect($session)->except(['permissions', 'menu'])->all()
            );
        }

        return null;
    }
}

if (!function_exists('authAccessToken')) {
    /**
     * The raw token, for forwarding on service-to-service calls.
     *
     *     Http::withToken(authAccessToken())->get($url);
     *
     * Forwarding the token is how downstream services learn who is acting and in which
     * tenant. It replaces passing a serialised user object around.
     */
    function authAccessToken(): ?string
    {
        return session('access_token') ?? request()?->bearerToken();
    }
}

if (!function_exists('authUserRoles')) {
    /**
     * Role names within the current tenant. Roles are tenant-scoped now, so the same user
     * can be an Accountant in one tenant and read-only in another.
     *
     * @return array<int,string>
     */
    function authUserRoles(): array
    {
        return session('auth_user.roles', []);
    }
}

if (!function_exists('authUserPermissions')) {
    /**
     * The effective permission map: [ 'accounts.control_accounts' => 2, 'fleet' => 3 ].
     *
     * Web requests read the session copy written at login. API requests resolve through
     * ResolvesPermissions, which is cached by pv — so this is cheap in both stacks.
     *
     * @return array<string,int>
     */
    function authUserPermissions(): array
    {
        if (session()->has('permissions')) {
            return session('permissions', []);
        }

        $tenantId = authTenantId();
        $userId = authUserId();

        if (!$tenantId || !$userId) {
            return [];
        }

        return app(ResolvesPermissions::class)->flatten($tenantId, $userId);
    }
}

if (!function_exists('moduleLevel')) {
    /**
     * Effective level for a module key, 0..3. Unknown or ungranted keys resolve to 0 —
     * absence means None, never "allow".
     */
    function moduleLevel(string $moduleKey): int
    {
        return (int) (authUserPermissions()[$moduleKey] ?? 0);
    }
}

if (!function_exists('userCan')) {
    /**
     * Ordinal permission check. Replaces scope-string matching.
     *
     *     userCan('invoices.sales_invoices')                  // at least Read
     *     userCan('invoices.sales_invoices', 'read_write')    // create/edit
     *     userCan('invoices.sales_invoices', 'full_control')  // delete / export
     *
     * NOTE: export is level 3 deliberately — bulk download is an exfiltration surface and
     * must not share a gate with the on-screen list.
     */
    function userCan(string $moduleKey, string $min = 'read'): bool
    {
        return moduleLevel($moduleKey) >= PermissionLevel::fromKey($min)->value;
    }
}

if (!function_exists('userCanAny')) {
    /**
     * True when ANY of the keys reaches the level — for a menu group or a shared screen.
     *
     * The old middlewares disagreed on this: the API one used array_intersect (any) and
     * the web one array_diff (all). Being explicit removes the ambiguity.
     *
     * @param  array<int,string>  $moduleKeys
     */
    function userCanAny(array $moduleKeys, string $min = 'read'): bool
    {
        foreach ($moduleKeys as $key) {
            if (userCan($key, $min)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('userCanAll')) {
    /**
     * @param  array<int,string>  $moduleKeys
     */
    function userCanAll(array $moduleKeys, string $min = 'read'): bool
    {
        foreach ($moduleKeys as $key) {
            if (!userCan($key, $min)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('userMenu')) {
    /**
     * The tile tree for the shell, already filtered to tenant_modules ∩ (level > None) and
     * nested. Render it directly — the front end needs no module knowledge.
     *
     * @return array<int,array>
     */
    function userMenu(): array
    {
        return session('menu', []);
    }
}

if (!function_exists('userObject')) {
    /**
     * @deprecated Do not send this to another service. Forward the access token instead —
     *             the receiving service reads the user and tenant from the signed claims,
     *             which a serialised object cannot prove.
     *
     *                 Http::withToken(authAccessToken())->post($url, $payload);
     *
     *             Still useful LOCALLY for audit columns and view models. Returns an array;
     *             the old version returned a JSON string built for cross-service transport
     *             that no longer happens.
     *
     * @return array<string,mixed>|null
     */
    function userObject(): ?array
    {
        $user = session('auth_user');

        if (!is_array($user) || empty($user['id'])) {
            return null;
        }

        return [
            'id' => $user['id'],
            'tenant_id' => authTenantId(),
            'name' => $user['full_name'] ?? null,
            'full_name' => $user['full_name'] ?? null,
            'email' => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
            'phone' => $user['phone'] ?? null,
            'user_type' => $user['user_type'] ?? null,
            'userable_type' => $user['userable_type'] ?? null,
            'userable_id' => $user['userable_id'] ?? null,
            'gender' => $user['gender'] ?? null,
            'is_active' => $user['is_active'] ?? null,
            'change_password' => $user['change_password'] ?? null,
        ];
    }
}

if (! function_exists('userMenu')) {
    /**
     * The tile tree for the shell, already filtered to tenant_modules ∩ (level > None) and
     * nested. Render it directly — the front end needs no module knowledge.
     *
     * @return array<int,array>
     */
    function userMenu(): array
    {
        return session('menu', []);
    }
}
 
if (! function_exists('authMenu')) {
    /**
     * The RENDER-READY menu: auth's visible tree (userMenu) enriched by MenuComposer with
     * ek-core routes + icons, pruned of anything not yet routable, active state resolved.
     *
     * Memoised per request — it renders in the layout on every page, and walking the tree
     * with Route::has() lookups should happen once, not per @include.
     *
     *     @include('partials.menu', ['items' => authMenu()])
     *
     * Use userMenu() when you want auth's raw tree (keys + levels) without presentation.
     *
     * @return array<int,array>
     */
    function authMenu(): array
    {
        return once(fn () => app(MenuComposer::class)
            ->compose(session('menu', [])));
    }
}

if (!function_exists('actingUser')) {
    /**
     * Get the current authenticated user's context snapshot.
     *
     * @return array{
     *     id: int|string|null,
     *     name: string|null,
     *     email: string|null,
     *     phone: string|null,
     *     roles: array
     * }
     */
    function actingUser(): array
    {
        $user = session('auth_user');
        return [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'roles' => $user['roles'] ?? [],
        ];
    }
}
