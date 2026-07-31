<?php

namespace App\Http\Controllers;

use App\Helpers\ServiceDiscoveryHelper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * ek-core front-end auth.
 *
 * WHAT CHANGED
 *
 *  - Tokens are TENANT-SCOPED. Login can now come back with `code: 2` and a list of
 *    tenants instead of a token: the user belongs to more than one and must choose.
 *    That path completes via a short-lived selection ticket — the password is never
 *    held anywhere to be replayed.
 *
 *  - The browser host is FORWARDED to auth (X-Forwarded-Host). Auth resolves the tenant
 *    from the subdomain, but this is a server-to-server call, so without the header auth
 *    would see `ek-auth:8000` and the subdomain would never resolve.
 *
 *  - Permissions no longer arrive as a flat scope list. The response body carries
 *    `user.permissions` (a { module_key: 0..3 } map) and `user.menu` (the tile tree).
 *    Both go in the session; Blade renders from them.
 *
 *  - `expires_in` is now SECONDS, not a unix timestamp.
 */
class AuthController extends Controller
{
    private const SESSION_TOKEN = 'access_token';
    private const SESSION_EXPIRES = 'token_expires_at';
    private const SESSION_TENANT = 'tenant';
    private const SESSION_USER = 'auth_user';
    private const SESSION_PERMS = 'permissions';
    private const SESSION_MENU = 'menu';
    private const SESSION_TICKET = 'tenant_selection_ticket';
    private const SESSION_TENANTS = 'tenant_options';

    public function index()
    {
        return view('auth.login');
    }

    /* ================================================================== *
     |  Login
     * ================================================================== */

    public function authenticateUser(Request $request): RedirectResponse
    {
        $this->validateLogin($request);

        try {
            $response = $this->attemptLogin($request);
            $payload = $response->json() ?? [];

            if ($response->failed()) {
                errorLogger(date('H') . '.error.log', json_encode([
                    'message' => 'Auth server rejected login',
                    'status' => $response->status(),
                    'body' => $payload,
                ]));
            }

            return $this->authenticationResponse($response->status(), $payload);

        } catch (\Throwable $exception) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]));

            return back()
                ->withErrors(['username' => 'The authentication service is unavailable. Please try again.'])
                ->withInput();
        }
    }

    private function attemptLogin(Request $request)
    {
        $url = ServiceDiscoveryHelper::serviceUrl('ek-auth', '/api/v1/login');

        return Http::asForm()
            // Auth resolves the tenant from the subdomain. This is server-to-server, so
            // the browser's host has to travel explicitly or auth sees its own hostname.
            ->withHeaders(['X-Forwarded-Host' => $request->getHost()])
            ->timeout(15)
            ->post($url, [
                'grant_type' => 'password',
                'client_id' => config('api.ek_auth_client_id'),
                'client_secret' => config('api.ek_auth_client_secret'),
                'username' => $request->username,
                'password' => $request->password,
                'channel' => 'web',
                'service_id' => config('api.current_system'),
            ]);
    }

    private function validateLogin(Request $request): void
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Username is required',
            'password.required' => 'Password is required',
        ]);
    }

    private function authenticationResponse(int $code, array $payload): RedirectResponse
    {
        if ($code === 200) {
            // Ambiguous membership — the user belongs to several tenants and must pick.
            // No token was issued; a short-lived ticket carries the flow instead.
            if (($payload['requires_tenant_selection'] ?? false) === true) {
                Session::put(self::SESSION_TICKET, $payload['selection_token'] ?? null);
                Session::put(self::SESSION_TENANTS, $payload['tenants'] ?? []);

                return redirect()->route('tenant-select');
            }

            return $this->establishSession($payload);
        }

        // No tenant, inactive account, or no access to the tenant this address serves.
        if ($code === 403) {
            return back()
                ->withErrors(['username' => $payload['message'] ?? 'Access denied.'])
                ->withInput();
        }

        if (in_array($code, [401, 422], true)) {
            return back()
                ->withErrors(['username' => $payload['message'] ?? 'Invalid credentials'])
                ->withInput();
        }

        return back()
            ->withErrors(['username' => 'Unexpected error occurred.'])
            ->withInput();
    }

    /* ================================================================== *
     |  Tenant selection
     * ================================================================== */

    public function showTenantSelection()
    {
        $tenants = Session::get(self::SESSION_TENANTS, []);

        if (empty($tenants) || !Session::has(self::SESSION_TICKET)) {
            return redirect()->route('login-index');
        }

        return view('auth.tenant-select', compact('tenants'));
    }

    public function selectTenant(Request $request): RedirectResponse
    {
        $request->validate(['tenant_id' => 'required|uuid']);

        $ticket = Session::get(self::SESSION_TICKET);

        if (!$ticket) {
            return redirect()->route('login-index')
                ->withErrors(['username' => 'Your session expired. Please sign in again.']);
        }

        // Guard against a tampered form: only tenants auth actually offered are allowed.
        $offered = collect(Session::get(self::SESSION_TENANTS, []))->pluck('id');

        if (!$offered->contains($request->tenant_id)) {
            return redirect()->route('login-index')
                ->withErrors(['username' => 'Invalid tenant selection.']);
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['X-Forwarded-Host' => $request->getHost()])
                ->timeout(15)
                ->post(ServiceDiscoveryHelper::serviceUrl('ek-auth', '/api/v1/tenant/select'), [
                    'selection_token' => $ticket,
                    'tenant_id' => $request->tenant_id,
                ]);

            $payload = $response->json() ?? [];

            if ($response->status() !== 200) {
                return redirect()->route('login-index')
                    ->withErrors(['username' => $payload['message'] ?? 'Tenant selection failed.']);
            }

            return $this->establishSession($payload);

        } catch (\Throwable $exception) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]));

            return redirect()->route('login-index')
                ->withErrors(['username' => 'The authentication service is unavailable.']);
        }
    }

    /**
     * Switching tenant for an already-signed-in user. Uses the live access token rather
     * than a ticket — auth re-checks membership, so a stale menu entry cannot get someone
     * into a tenant they were removed from.
     */
    public function switchTenant(Request $request): RedirectResponse
    {
        $request->validate(['tenant_id' => 'required|uuid']);

        try {
            $response = Http::asForm()
                ->withHeaders(['X-Forwarded-Host' => $request->getHost()])
                ->timeout(15)
                ->post(ServiceDiscoveryHelper::serviceUrl('ek-auth', '/api/v1/tenant/select'), [
                    'access_token' => Session::get(self::SESSION_TOKEN),
                    'tenant_id' => $request->tenant_id,
                ]);

            $payload = $response->json() ?? [];

            if ($response->status() !== 200) {
                return back()->withErrors(['tenant' => $payload['message'] ?? 'Could not switch tenant.']);
            }

            return $this->establishSession($payload);

        } catch (\Throwable $exception) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => $exception->getMessage(),
            ]));

            return back()->withErrors(['tenant' => 'The authentication service is unavailable.']);
        }
    }

    /* ================================================================== *
     |  Session
     * ================================================================== */

    /**
     * Stores everything the shell needs for the session. The token is scoped to one
     * tenant, so tenant, permissions and menu are all replaced together — never merged
     * with what was there before, or a tenant switch would leave stale tiles behind.
     */
    private function establishSession(array $payload): RedirectResponse
    {
        $token = $payload['token'] ?? null;
        $user = $payload['user'] ?? null;

        if (!$token || !$user) {
            return redirect()->route('login-index')
                ->withErrors(['username' => 'Authentication failed.']);
        }

        Session::forget([self::SESSION_TICKET, self::SESSION_TENANTS]);

        // Regenerate on privilege change — login and tenant switch both qualify.
        Session::regenerate();

        Session::put(self::SESSION_TOKEN, $token);
        Session::put(self::SESSION_EXPIRES, now()->addSeconds((int) ($payload['expires_in'] ?? 3600)));
        Session::put(self::SESSION_TENANT, $payload['tenant'] ?? null);
        Session::put(self::SESSION_USER, $user);
        Session::put(self::SESSION_PERMS, $user['permissions'] ?? []);
        Session::put(self::SESSION_MENU, $user['menu'] ?? []);

        $this->hydrateAuthUser($user);

        if (!empty($user['change_password'])) {
            return redirect()->route('password-change')
                ->with('status', 'Please set a new password before continuing.');
        }

        return redirect()->intended('home-page');
    }

    /**
     * ek-core no longer owns the users table — auth does. This is a non-persisted model
     * carrying identity for the request only.
     *
     * NOTE: Auth::setUser() lasts one request. A middleware must call this on every
     * request from the session copy, e.g.:
     *
     *     if ($user = session('auth_user')) { app(AuthController::class)->hydrateAuthUser($user); }
     */
    public function hydrateAuthUser(array $user): void
    {
        Auth::setUser(new User(collect($user)->except(['permissions', 'menu'])->all()));
    }

    /* ================================================================== *
     |  Logout
     * ================================================================== */

    public function logout(Request $request): RedirectResponse
    {
        $token = Session::get(self::SESSION_TOKEN);

        // Best effort — a failed revoke must never trap the user in a session.
        if ($token) {
            try {
                Http::withToken($token)
                    ->timeout(5)
                    ->post(ServiceDiscoveryHelper::serviceUrl('ek-auth', '/api/v1/logout'));
            } catch (\Throwable $exception) {
                errorLogger(date('H') . '.error.log', json_encode([
                    'message' => 'Token revoke failed on logout: ' . $exception->getMessage(),
                ]));
            }
        }

        Auth::logout();
        Session::flush();
        Session::regenerate();

        return redirect()->route('login-index');
    }
}