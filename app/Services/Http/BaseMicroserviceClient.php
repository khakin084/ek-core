<?php

namespace App\Services\Http;

use App\Helpers\ServiceDiscoveryHelper;
use App\Services\AuthenticationService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseMicroserviceClient
{
    /** Subclass sets this — the service most of its calls target. */
    protected string $defaultService;

    protected int $timeoutSeconds = 6;
    protected int $retryTimes = 2;
    protected int $retrySleepMs = 150;

    public function __construct(
        protected ServiceDiscoveryHelper $serviceDiscovery,
        protected AuthenticationService $auth,
    ) {
    }

    // ---- token-scoped request builders ----

    protected function userRequest(string $path = '', ?int $timeoutSeconds = null): PendingRequest
    {
        return $this->baseRequest($path, $timeoutSeconds)->withToken(session('access_token'));
    }

    protected function serviceRequest(string $path = '', ?int $timeoutSeconds = null): PendingRequest
    {
        return $this->baseRequest($path, $timeoutSeconds)->withToken($this->auth->serviceToken());
    }

    private function baseRequest(string $path, ?int $timeoutSeconds): PendingRequest
    {
        $timeout = $timeoutSeconds ?? $this->timeoutSeconds;
        $headers = ['X-Correlation-ID' => $this->correlationId()];

        if (app()->runningInConsole() === false) {
            foreach (['X-AUDIT-MODULE', 'X-AUDIT-ENTITY', 'X-AUDIT-RECORD-ID'] as $h) {
                $val = request()->header($h);
                if (!empty($val)) {
                    $headers[$h] = $val;
                }
            }
        }

        return Http::timeout($timeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->acceptJson()
            ->asJson()
            ->withHeaders($headers);
    }

    // ---- generic CRUD quartet ----

    public function fetchResource(string $path, string $actionName, array $query = [], TokenType $as = TokenType::User, ?string $service = null): ?array
    {
        $res = $this->requestAs($as, $path)->get($this->url($path, $service), $query);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);
        return null;
    }

    public function listResource(string $path, string $actionName, array $query = [], TokenType $as = TokenType::User, ?string $service = null): array
    {
        $res = $this->requestAs($as, $path)->get($this->url($path, $service), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);
        return [];
    }

    /**
     * Like listResource(), but for DataTables server-side responses: returns the raw envelope
     * (draw/recordsTotal/recordsFiltered/data) untouched — parseJson()'s data-key unwrapping
     * would strip recordsTotal/recordsFiltered and break DataTables' redraw.
     */
    public function listResourceForDataTable(string $path, string $actionName, array $query = [], TokenType $as = TokenType::User, ?string $service = null): array
    {
        $res = $this->requestAs($as, $path)->get($this->url($path, $service), $query);

        if ($res->successful()) {
            return $res->json() ?? $this->emptyDataTableResponse($query);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);

        return $this->emptyDataTableResponse($query);
    }

    /**
     * A well-formed empty page so a failed/malformed downstream response doesn't crash
     * DataTables' redraw (it reads recordsTotal/recordsFiltered unconditionally).
     */
    protected function emptyDataTableResponse(array $query = []): array
    {
        return [
            'draw' => (int) ($query['draw'] ?? 0),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];
    }

    public function storeResource(string $path, array $payload, string $actionName, TokenType $as = TokenType::User, ?string $service = null): ?array
    {
        $res = $this->requestAs($as, $path)->post($this->url($path, $service), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redact($payload)]);
        return null;
    }

    public function updateResource(string $path, array $payload, string $actionName, TokenType $as = TokenType::User, ?string $service = null): ?array
    {
        $res = $this->requestAs($as, $path)->put($this->url($path, $service), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redact($payload)]);
        return null;
    }

    public function deleteResource(string $path, string $actionName, TokenType $as = TokenType::User, ?string $service = null): bool
    {
        $res = $this->requestAs($as, $path)->delete($this->url($path, $service));

        if ($res->successful()) {
            return true;
        }

        $this->logFailure($actionName, $res, ['path' => $path]);
        return false;
    }

    private function requestAs(TokenType $as, string $path): PendingRequest
    {
        return $as === TokenType::Service ? $this->serviceRequest($path) : $this->userRequest($path);
    }

    // ---- url building — defaults to $defaultService, overridable per call ----

    protected function url(string $path, ?string $service = null): string
    {
        $base = rtrim($this->serviceDiscovery->serviceUrl($service ?? $this->defaultService, ''), '/');
        return $base . '/' . ltrim($path, '/');
    }

    // ---- shared response/logging plumbing ----

    protected function parseJson(Response $res): ?array
    {
        $json = $res->json();

        if (!is_array($json)) {
            return null;
        }

        return array_key_exists('data', $json)
            ? (is_array($json['data']) ? $json['data'] : $json)
            : $json;
    }

    protected function correlationId(): string
    {
        $existing = request()?->header('X-Correlation-ID');
        return !empty($existing) ? $existing : (string) Str::uuid();
    }

    protected function logFailure(string $action, Response $res, array $context = []): void
    {
        $payload = [
            'action' => $action,
            'status' => $res->status(),
            'body' => $this->safeBody($res),
            'url' => (string) $res->effectiveUri(),
            'correlation_id' => $this->correlationId(),
        ] + $context;

        if (function_exists('errorLogger')) {
            errorLogger(date('H') . '.error.log', static::class . '_ERROR::' . json_encode($payload));
        }

        Log::error(static::class . ' request failed', $payload);
    }

    protected function safeBody(Response $res): string
    {
        $body = (string) $res->body();
        return strlen($body) > 5000 ? substr($body, 0, 5000) . '...<truncated>' : $body;
    }

    protected function redact(array $payload, array $extraKeys = []): array
    {
        foreach (array_merge(['password', 'passconf', 'password_confirmation'], $extraKeys) as $secret) {
            if (array_key_exists($secret, $payload)) {
                $payload[$secret] = '***';
            }
        }
        return $payload;
    }
}