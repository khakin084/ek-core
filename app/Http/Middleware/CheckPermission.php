<?php

namespace App\Http\Middleware;

use App\Contracts\ResolvesPermissions;
use App\Enums\PermissionLevel;
use Closure;
use Illuminate\Http\Request;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces an ordinal permission level on a LEAF module.
 *
 *     Route::get('/control-accounts',  ...)->middleware('perm:accounts.control_accounts,read');
 *     Route::post('/control-accounts', ...)->middleware('perm:accounts.control_accounts,read_write');
 *     Route::delete('/control-accounts/{id}', ...)->middleware('perm:accounts.control_accounts,full_control');
 *
 *     // Export is a level-3 action — bulk download is an exfiltration surface, so it does
 *     // NOT share a gate with the on-screen list.
 *     Route::get('/control-accounts/export', ...)->middleware('perm:accounts.control_accounts,full_control');
 *
 * Requires AuthenticateJwt to have run first.
 *
 * IN THE AUTH SERVICE this resolves locally via PermissionResolver. In a downstream
 * service, bind PermissionResolver to a remote implementation that fetches
 * GET /internal/permissions/{tenant}/{user} and caches by pv — the contract is the same
 * levelFor($tenant, $user, $key) call, so this class does not change.
 */
class CheckPermission
{
    public function __construct(private readonly ResolvesPermissions $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $module, string $min = 'read'): Response
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('user_id');

        if (! $tenantId || ! $userId) {
            // AuthenticateJwt did not run, or ran after this. A misordered stack must not
            // fall through as "allowed".
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // A bad level string in a route definition is a deploy-time bug, not a user error.
        $required = PermissionLevel::fromKey($min);

        try {
            $level = $this->permissions->levelFor($tenantId, $userId, $module);

        } catch (LogicException $e) {
            // Route points at a container. Containers are menu visibility only and grant
            // nothing — this is a programming error, and answering 403 would hide it.
            report($e);

            return response()->json([
                'message' => 'Route is misconfigured: permission checks must target a leaf module.',
            ], 500);

        } catch (RuntimeException $e) {
            // Unknown module key — usually a route shipped before modules:sync ran.
            report($e);

            return response()->json(['message' => 'Permission target is not registered.'], 500);
        }

        if ($level < $required->value) {
            // Web routes get an HTML 403; API routes get JSON. The same middleware serves
            // both stacks, so it must not force JSON onto a browser.
            if (! $request->expectsJson()) {
                abort(403, 'You do not have permission to perform this action.');
            }

            return response()->json([
                'message'  => 'You do not have permission to perform this action.',
                'module'   => $module,
                'required' => $required->key(),
            ], 403);
        }

        return $next($request);
    }
}