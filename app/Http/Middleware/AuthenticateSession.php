<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-based token verification for the ek-core web shell. Replaces WebAuthMiddleware.
 *
 * The API sibling (AuthenticateJwt) reads a bearer header and answers 401; this one reads
 * the session and redirects to login. Everything after it — CheckPermission included —
 * behaves identically, because both set the same request attributes.
 *
 * WHAT WAS DROPPED FROM WebAuthMiddleware
 *
 *  - $requiredServiceId audience check. Per-service tokens are gone; one token is valid
 *    across every service, so enforcing service_id would reject valid sessions.
 *
 *  - ...$requiredScopes. Scopes left the token. Route-level checks are now
 *    `perm:module.key,level` via CheckPermission, and the ANY-vs-ALL ambiguity between the
 *    old web and API middlewares (array_diff vs array_intersect) disappears with it.
 *
 *  - Cache::put("access_token_{user}"). That cache existed to feed the per-service token
 *    exchange, which no longer exists. Parking user bearer tokens in a shared cache is also
 *    a needless place for them to leak.
 *
 *  - json_decode() of the subject. `sub` is a plain uuid now, not a JSON blob.
 */
class AuthenticateSession
{
    /** Refresh when the token has less than this long to live. */
    private const REFRESH_WINDOW_SECONDS = 300;

    public function __construct(private readonly AuthenticationService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = Session::get('access_token');

        if (! $accessToken) {
            return redirect()->route('login-index');
        }

        $verify = $this->auth->verifyTokenFromKey($accessToken);

        if (empty($verify['success'])) {
            return $this->reject($request, 'Your session has expired. Please sign in again.');
        }

        $user = Session::get('auth_user');

        if (! is_array($user) || empty($user['id'])) {
            return $this->reject($request, 'Invalid session. Please sign in again.');
        }

        // The token is scoped to one tenant. If the browser is on a tenant subdomain that
        // no longer matches, the session must not silently keep serving the old tenant's
        // data under the new address.
        if (! $this->tenantMatchesHost($request, $verify['tenantId'])) {
            return $this->reject($request, 'Please sign in to continue on this address.');
        }

        $accessToken = $this->refreshIfExpiring($accessToken, $verify);

        // Same attribute names as AuthenticateJwt, so CheckPermission is shared.
        $request->attributes->set('user_id', $verify['userId']);
        $request->attributes->set('tenant_id', $verify['tenantId']);
        $request->attributes->set('permission_version', $verify['permissionVersion']);
        $request->attributes->set('token_claims', $verify['tokenInfo']);

        // Auth::setUser() lasts one request — rehydrate from the session copy every time.
        // ek-core no longer owns the users table, so this model is never persisted.
        Auth::setUser(new User(collect($user)->except(['permissions', 'menu'])->all()));

        return $next($request);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Keeps an active session alive without a re-login, and picks up a bumped
     * permission_version at the same time.
     */
    private function refreshIfExpiring(string $accessToken, array $verify): string
    {
        $secondsLeft = ($verify['expiresAt'] ?? 0) - time();

        if ($secondsLeft > self::REFRESH_WINDOW_SECONDS) {
            return $accessToken;
        }

        $refreshed = $this->auth->refresh($accessToken);

        if ($refreshed) {
            Session::put('access_token', $refreshed);

            return $refreshed;
        }

        // Refresh failed — let the current token run out rather than dropping the user now.
        return $accessToken;
    }

    /**
     * Null when the host is not a tenant subdomain (api., www., bare domain, local dev),
     * in which case there is nothing to compare and the session stands.
     */
    private function tenantMatchesHost(Request $request, ?string $tenantId): bool
    {
        $sessionTenant = Session::get('tenant');
        $host          = strtolower($request->getHost());
        $base          = strtolower((string) config('api.base_domain'));

        if (empty($base) || $host === $base || ! str_ends_with($host, '.' . $base)) {
            return true;
        }

        $slug = substr($host, 0, -(strlen($base) + 1));

        if (str_contains($slug, '.') || in_array($slug, ['www', 'api', 'app', 'auth', 'admin'], true)) {
            return true;
        }

        return isset($sessionTenant['slug']) && $sessionTenant['slug'] === $slug;
    }

    private function reject(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login-index')->withErrors(['username' => $message]);
    }
}
