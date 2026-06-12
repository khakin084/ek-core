<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuthService;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $requiredServiceId = null, ...$requiredScopes): Response
    {
        $accessToken = session('access_token');

        if (!$accessToken) {
            return redirect()->route('login-index');
        }

        $verify = app(AuthService::class)->verifyTokenFromKey($accessToken);

        if (empty($verify['success'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login-index')
                ->withErrors(['username' => 'Invalid or expired session. Please log in again.']);
        }

        // Expect: user payload + scopes from auth server
        $userPayload = $verify['user'] ?? $verify['userId'] ?? null; // keep backward compat with your response
        $userScopes = $verify['scopes'] ?? [];
        $serviceId = $verify['service_id'] ?? $verify['serviceId'] ?? null;

        if (is_string($userPayload)) {
            $userPayload = json_decode($userPayload, true);
        }

        if (!is_array($userPayload) || empty($userPayload['id'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login-index')
                ->withErrors(['username' => 'Invalid session payload. Please log in again.']);
        }

        // Enforce service audience (if provided)
        if ($requiredServiceId && $serviceId && $serviceId !== $requiredServiceId) {
            abort(403, 'Forbidden: Invalid service audience');
        }

        // Attach auth context (don’t pollute request input)
        $request->attributes->set('userId', $userPayload['id']);
        $request->attributes->set('permissions', $userScopes);

        // Set Laravel auth user for Blade/controllers
        Auth::setUser(new User($userPayload));

        // Optional: store user in session (keep it if your views depend on it)
        session(['user' => $userPayload]);

        // Cache token by user id (simple + explicit)
        Cache::put("access_token_{$userPayload['id']}", $accessToken, now()->addMinutes(6));

        // Scope enforcement (ALL required scopes). For ANY, use array_intersect check instead.
        if (!empty($requiredScopes)) {
            $missing = array_diff($requiredScopes, $userScopes);
            if (!empty($missing)) {
                abort(403, 'Forbidden: Insufficient Permissions');
            }
        }

        return $next($request);
    }
}
