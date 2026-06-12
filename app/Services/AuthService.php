<?php

namespace App\Services;

use App\Helpers\ServiceDiscoveryHelper;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    /**
     * @throws ConnectionException
     */
    public function redirectUser()
    {
        $auth_url = $this->getAuthUrl('/oauth/token');
        $response = Http::asForm()->post($auth_url, [
            'grant_type' => 'client_credentials',
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'scope' => '*',
        ]);
        return $response->json()['access_token'];
    }

    /**
     * @throws ConnectionException
     */
    public function verifyToken($token)
    {
        
        try {
            $auth_url = $this->getAuthUrl('/api/oauth/verify-token');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])->get($auth_url);

            return $response->json();

        } catch (Exception $e) {
            Log::error('Error verifying token', [
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Server error occurred during token verification',
            ];
        }
    }

    public function verifyTokenFromKey($tokenString): array
    {
        try {
            $publicKey = file_get_contents(storage_path("app/private/keys/oauth-public.key"));
            $decodedToken = JWT::decode($tokenString, new Key($publicKey, 'RS256'));

            if (base64_decode($decodedToken->secret) !== base64_decode(config('api.ek_auth_secret'))) {
                throw new Exception('Invalid Secret Key.');
            }

            if ($decodedToken->iss !== $this->getAuthUrl('')) {
                throw new Exception('Invalid Issuer.');
            }

            if ($decodedToken->exp < time()) {
                throw new Exception('Token has expired.');
            }

            if ($decodedToken->nbf > time()) {
                throw new Exception('Token is not yet valid.');
            }

            if ($decodedToken->service_id !== config('api.current_system')) {
                throw new Exception('Token is not valid for this service.');
            }

            return [
                'tokenInfo' => $decodedToken,
                'userId' => $decodedToken->sub,
                'scopes' => $decodedToken->scopes,
                'service_id' => $decodedToken->service_id,
                'expires_at' => $decodedToken->exp,
                'success' => true,
                'error' => '',
            ];

        } catch (Exception $e) {
            errorLogger(null, 'Error verifying token::' . json_encode([
                'error' => $e->getMessage(),
            ]));
            return [
                'error' => $e->getMessage(),
                'tokenInfo' => null,
                'success' => false
            ];
        }
    }


    public function requestNewToken($currentAccessToken, $newServiceId)
    {

        try {

            $userId = authUserId();
            $cacheKey = "service_token:{$newServiceId}:{$userId}";

            // Check cache first
            $cachedToken = Cache::get($cacheKey);

            if ($cachedToken && now()->timestamp >= $cachedToken['expires_in']) {
                Cache::forget($cacheKey);  // Explicitly remove expired token
                $cachedToken = null;      // Ensure it's not used
            }

            // If a valid cached token exists, return it
            if ($cachedToken) {
                return $cachedToken['token'];
            }

            $auth_url = $this->getAuthUrl('/api/oauth/refresh-token');
            $response = Http::asForm()->post($auth_url, [
                'access_token' => $currentAccessToken,
                'service_id' => $newServiceId,
                'client_id' => 'imis',
            ]);


            if ($response->successful()) {
                $data = $response->json();
                //dd($data);

                $publicKey = file_get_contents(storage_path("app/private/keys/oauth-public.key"));
                $decodedToken = JWT::decode($data['token'], new Key($publicKey, 'RS256'));
                $expiry = $decodedToken->exp;

                //Auth::setUser(new User($data['user']));

                //session(['access_token' => $data['token']]);

                $tokenInfo = [
                    'token' => $data['token'],
                    'expires_in' => $expiry
                ];


                $ttl = $expiry - now()->timestamp;
                Cache::put($cacheKey, $tokenInfo, now()->addSeconds($ttl));

                return $data['token'];

            }

            return ['success' => false, 'error' => 'Failed to obtain new token.'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAuthUrl($endpoint = null)
    {
        return ServiceDiscoveryHelper::serviceUrl('ek-auth', $endpoint);
    }



}
