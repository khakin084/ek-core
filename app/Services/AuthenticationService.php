<?php

namespace App\Services;

use App\Helpers\ServiceDiscoveryHelper;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * The single auth client. Identical in ek-auth and in every downstream service —
 * replaces AuthService (downstream) and AuthServerService (auth).
 *
 * WHAT WAS REMOVED, AND WHY
 *
 *  1. THE SECRET CHECK. `$decodedToken->secret` no longer exists. It was never a control:
 *     JWT payloads are base64url, not encrypted, so that value was readable by every token
 *     holder — comparing it to a local copy proved nothing the RS256 signature does not
 *     already prove. If the old shared secret is still deployed anywhere, rotate it: it has
 *     been travelling in plaintext inside every token ever issued.
 *
 *  2. THE service_id CHECK. Under per-service token bundles a token was only valid for the
 *     service it named. Bundles are gone — one token now works everywhere, because every
 *     service verifies the same signature and resolves rights from tid + pv. Enforcing
 *     service_id would reject every valid token outside its minting service. The claim
 *     remains as session context (which app the user signed in from), not as a boundary.
 *
 *  3. scopes. Permissions left the token. Services resolve them through
 *     ResolvesPermissions, cached by pv.
 *
 * WHAT WAS ADDED
 *
 *  - tid / pv are now REQUIRED. A token with no tenant claim is rejected outright; treating
 *    a missing tid as "all tenants" is the failure this design exists to prevent.
 *  - Issuer key corrected to 'ek-auth'. The downstream copy compared against the right
 *    service, but the auth-side copy used 'auth' and could never match.
 *  - The public key is read once per process, not once per verification.
 */
class AuthenticationService
{
    private const AUTH_SERVICE = 'ek-auth';

    private static ?string $publicKey = null;

    /* ================================================================== *
     |  Verification — the hot path
     * ================================================================== */

    /**
     * Local verification against auth's public key. No network call.
     *
     * @return array{
     *     success: bool, error: string, tokenInfo: object|null, userId: string|null,
     *     tenantId: string|null, permissionVersion: int|null, expiresAt: int|null
     * }
     */
    public function verifyTokenFromKey(?string $tokenString): array
    {
        if (empty($tokenString)) {
            return $this->failure('No token supplied.');
        }

        try {
            JWT::$leeway = 30;   // tolerate modest clock drift between containers

            // decode() already enforces signature, exp and nbf — the manual time checks in
            // the old version were redundant.
            $claims = JWT::decode($tokenString, new Key($this->publicKey(), 'RS256'));

            $this->assertIssuer($claims);
            $this->assertTenantScoped($claims);

            return [
                'success'           => true,
                'error'             => '',
                'tokenInfo'         => $claims,
                'userId'            => $claims->sub,
                'tenantId'          => $claims->tid,
                'permissionVersion' => (int) $claims->pv,
                'expiresAt'         => (int) $claims->exp,
            ];

        } catch (ExpiredException) {
            return $this->failure('Token has expired.');
        } catch (BeforeValidException) {
            return $this->failure('Token is not yet valid.');
        } catch (SignatureInvalidException $e) {
            // Forgery, or a key rotation this service has not picked up.
            return $this->failure('Token signature is invalid.', $e);
        } catch (Throwable $e) {
            return $this->failure($e->getMessage(), $e);
        }
    }

    /**
     * Remote introspection against auth.
     *
     * NOT for the request path — use verifyTokenFromKey(), which needs no network. This is
     * for debugging, for clients that cannot verify a JWT, and for the one thing local
     * verification cannot see: whether the token was revoked or the membership deactivated
     * since it was minted. Auth also returns pv_stale here.
     */
    public function verifyToken(string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->get($this->getAuthUrl('/api/v1/verify-token'));

            return $response->json() ?? ['isValid' => false, 'message' => 'Empty response.'];

        } catch (Throwable $e) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => 'Remote token verification failed: ' . $e->getMessage(),
            ]));

            return ['isValid' => false, 'message' => 'Token verification unavailable.'];
        }
    }

    /* ================================================================== *
     |  Tokens
     * ================================================================== */

    /**
     * This service's own machine credential, for internal endpoints such as
     * /internal/permissions and /internal/approval-policy. Not a user token.
     *
     * Cached slightly short of its lifetime so it is replaced before it expires.
     */
    public function serviceToken(): string
    {
        return Cache::remember('auth:service-token', 3300, function () {
            $response = Http::asForm()->timeout(5)->post($this->getAuthUrl('/oauth/token'), [
                'grant_type'    => 'client_credentials',
                // config(), not env() — env() returns null once config is cached.
                // The DEDICATED client-credentials client, not the user-login client: a
                // password/authcode client cannot use this grant (unauthorized_client).
                'client_id'     => config('api.ek_auth_service_id'),
                'client_secret' => config('api.ek_auth_service_secret'),
                'scope'         => '*',
            ]);

            if ($response->failed()) {
                throw new RuntimeException('Could not obtain a service token from auth.');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Re-issues the caller's token for the SAME user and tenant, picking up the current
     * permission_version. Scope never widens — use auth's /tenant/select to change tenant.
     */
    public function refresh(string $accessToken): ?string
    {
        try {
            $response = Http::asForm()->timeout(5)->post(
                $this->getAuthUrl('/api/v1/refresh-token'),
                ['access_token' => $accessToken]
            );

            return $response->successful() ? $response->json('token') : null;

        } catch (Throwable $e) {
            errorLogger(date('H') . '.error.log', json_encode([
                'message' => 'Token refresh failed: ' . $e->getMessage(),
            ]));

            return null;
        }
    }

    /**
     * @deprecated Per-service tokens no longer exist. One token is valid across every
     *             service, so there is nothing to exchange — this returns the token it was
     *             given. Kept so existing call sites keep working; delete them as you go.
     */
    public function requestNewToken(string $currentAccessToken, ?string $newServiceId = null): string
    {
        errorLogger(date('H') . '.info.log', json_encode([
            'message'    => 'requestNewToken() is deprecated — one token is valid service-wide.',
            'service_id' => $newServiceId,
        ]));

        return $currentAccessToken;
    }

    public function getAuthUrl(?string $endpoint = null): string
    {
        return ServiceDiscoveryHelper::serviceUrl(self::AUTH_SERVICE, $endpoint);
    }

    /* ================================================================== *
     |  Internals
     * ================================================================== */

    private function assertIssuer(object $claims): void
    {
        // Two legitimate minters produce two different `iss` values, both from auth:
        //   - user tokens: minted by IssueTokenService -> serviceUrl('ek-auth')
        //   - service tokens: minted by Passport's /oauth/token -> config('app.url')
        // Accept either. A token from anywhere else is rejected.
        $accepted = array_map(
            fn ($v) => rtrim((string) $v, '/'),
            array_filter([
                $this->getAuthUrl(''),
                config('app.url'),
                config('auth_service.issuer'),   // optional explicit override
            ])
        );

        $iss = isset($claims->iss) ? rtrim($claims->iss, '/') : null;

        if ($iss === null || ! in_array($iss, $accepted, true)) {
            throw new RuntimeException('Invalid issuer.');
        }
    }

    /**
     * Every user token names the tenant it is scoped to. Missing tid means a pre-cutover
     * token or a tenant-selection ticket — neither may reach tenant data.
     */
    private function assertTenantScoped(object $claims): void
    {
        if (empty($claims->sub)) {
            throw new RuntimeException('Token has no subject.');
        }

        if (empty($claims->tid)) {
            throw new RuntimeException('Token is not scoped to a tenant.');
        }

        if (! isset($claims->pv)) {
            throw new RuntimeException('Token has no permission version.');
        }
    }

    /**
     * Auth's public verification key, held for the life of the process — it changes only
     * on rotation, which is a deploy.
     *
     * The path differed per service (storage/oauth-public.key in auth,
     * storage/app/private/keys/oauth-public.key downstream). Set
     * auth_service.public_key_path explicitly; the fallbacks keep both layouts working.
     */
    private function publicKey(): string
    {
        if (self::$publicKey !== null) {
            return self::$publicKey;
        }

        $candidates = array_filter([
            config('auth_service.public_key_path'),
            storage_path('oauth-public.key'),
            storage_path('app/private/keys/oauth-public.key'),
        ]);

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return self::$publicKey = file_get_contents($path);
            }
        }

        throw new RuntimeException(
            'Auth public key not found. Checked: ' . implode(', ', $candidates)
        );
    }

    private function failure(string $message, ?Throwable $e = null): array
    {
        errorLogger(date('H') . '.error.log', json_encode([
            'message' => 'Token verification failed: ' . $message,
            'trace'   => $e?->getTraceAsString(),
        ]));

        return [
            'success'           => false,
            'error'             => $message,
            'tokenInfo'         => null,
            'userId'            => null,
            'tenantId'          => null,
            'permissionVersion' => null,
            'expiresAt'         => null,
        ];
    }
}