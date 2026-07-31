<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the bearer token locally and puts identity on the request.
 *
 * Runs BEFORE CheckPermission, which reads the attributes this sets. Verification is
 * local — signature against auth's public key — so there is no per-request call to the
 * auth service.
 *
 * Register in bootstrap/app.php:
 *
 *     $middleware->alias([
 *         'jwt'  => \App\Http\Middleware\AuthenticateJwt::class,
 *         'perm' => \App\Http\Middleware\CheckPermission::class,
 *     ]);
 *
 * Then: ->middleware(['jwt', 'perm:accounts.control_accounts,read_write'])
 */
class AuthenticateJwt
{
    public function __construct(private readonly AuthenticationService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->auth->verifyTokenFromKey($request->bearerToken());

        if (! $result['success']) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error'   => $result['error'],
            ], 401);
        }

        // Everything downstream reads these — never a header or body field, which the
        // caller controls. tid comes from the signed token or not at all.
        $request->attributes->set('user_id', $result['userId']);
        $request->attributes->set('tenant_id', $result['tenantId']);
        $request->attributes->set('permission_version', $result['permissionVersion']);
        $request->attributes->set('token_claims', $result['tokenInfo']);

        return $next($request);
    }
}
