<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @throws ConnectionException
     */
    public function handle(Request $request, Closure $next, ...$requiredScopes): ?Response
    {
        if (!empty($request->header('authorization'))) {

            $accessToken = $request->bearerToken();

            $verify = (new AuthService())->verifyTokenFromKey($accessToken);

            if ($verify['success'] === true)
            {

                $request->merge(['userId' => $verify['userId']]);

                $userScopes = $verify['scopes'] ?? [];

                if (!empty($requiredScopes) && !array_intersect($requiredScopes, $userScopes)) {
                    return response()->json(['message' => 'Forbidden: Insufficient Permissions'],
                        403);
                }

                return $next($request);

            } else {
                return response()->json(['message' => 'Unauthorized',],
                    401);
            }
        }

        return response()->json(['message' => 'Authorization header is missing or invalid.',],
            401);
    }


}
