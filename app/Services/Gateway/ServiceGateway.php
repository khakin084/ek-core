<?php

namespace App\Services\Gateway;

use App\Services\AuthenticationService;
use App\Helpers\ServiceDiscoveryHelper;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Base class for every service gateway the BFF talks to.
 *
 * Why gateways exist: an index must not scatter Http::get() calls with ad-hoc error
 * handling. Each downstream service gets one gateway that owns its base URL, its auth, its
 * timeouts, and its failure posture — so controllers compose typed data and never see a
 * raw HTTP response.
 *
 * AUTH POSTURE: gateways forward the END USER's token (the request's bearer / session
 * token), so the downstream service enforces that user's tenant and permissions. They do
 * NOT use the service token for user-initiated reads — that would bypass per-user access
 * control. The service token is only for genuinely machine-facing endpoints.
 *
 * FAILURE POSTURE: read gateways FAIL SOFT (return null / [] so the page degrades). This is
 * the opposite of the permission resolver, which fails CLOSED — because here a failure hides
 * data, while there a failure could expose it.
 */
abstract class ServiceGateway
{
    abstract protected function serviceName(): string;

    public function __construct(protected readonly AuthenticationService $auth)
    {
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->userToken())
            ->acceptJson()
            ->timeout(5)
            ->retry(2, 150, throw: false);
    }

    protected function url(string $endpoint): string
    {
        return ServiceDiscoveryHelper::serviceUrl($this->serviceName(), $endpoint);
    }

    /**
     * GET that never throws. Returns $default on any failure and logs it — the caller
     * decides how a missing section renders.
     */
    protected function get(string $endpoint, array $query = [], mixed $default = null): mixed
    {
        try {
            $response = $this->client()->get($this->url($endpoint), $query);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logFailure($endpoint, $response->status(), $response->body());

        } catch (Throwable $e) {
            $this->logFailure($endpoint, 0, $e->getMessage());
        }

        return $default;
    }

    /**
     * The end user's token for this request — session (web) or bearer (api).
     */
    private function userToken(): ?string
    {
        return session('access_token') ?? request()?->bearerToken();
    }

    private function logFailure(string $endpoint, int $status, string $detail): void
    {
        errorLogger(date('H') . '.error.log', json_encode([
            'message'  => 'Gateway call failed (degrading, not failing)',
            'service'  => $this->serviceName(),
            'endpoint' => $endpoint,
            'status'   => $status,
            'detail'   => mb_substr($detail, 0, 500),
        ]));
    }
}
