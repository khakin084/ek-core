<?php

namespace Modules\UserMgt\Services;

use App\Helpers\ServiceDiscoveryHelper;
use App\Services\AuthenticationService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserMgtService
{
    protected string $baseUrl;

    protected int $timeoutSeconds = 6;
    protected int $retryTimes = 2;
    protected int $retrySleepMs = 150;

    protected int $userCacheTtlSeconds = 600; // 10 minutes

    /**
     * Endpoints under these prefixes are MACHINE-FACING (guarded by EnsureClientToken in
     * ek-auth). Calls to them authenticate with the SERVICE token, not the user's — sending
     * the user token yields 403 "This endpoint requires a service token".
     *
     * Everything else keeps forwarding the user's token so ek-auth enforces that user's own
     * tenant and permissions.
     */
    protected array $internalPrefixes = [
        '/api/v1/internal/',
    ];

    public function __construct(
        protected ServiceDiscoveryHelper $serviceDiscovery,
        protected AuthenticationService $auth,
    ) {
        $this->baseUrl = rtrim($this->serviceDiscovery->serviceUrl('ek-auth', ''), '/');
    }

    /**
     * --------------------------
     * Public API
     * --------------------------
     */

    public function getUser(int|string $id, bool $useCache = true): ?array
    {
        $cacheKey = $this->cacheKeyUser($id);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $path = "/api/usermgt/v1/users/get/{$id}";
        $res = $this->request($path)->get($this->url($path));

        if ($res->successful()) {
            $data = $this->parseJson($res);

            if ($useCache && is_array($data)) {
                Cache::put($cacheKey, $data, $this->userCacheTtlSeconds);
            }

            return $data;
        }

        $this->logFailure('getUser', $res, ['id' => $id]);

        return null;
    }

    public function searchUsers(string $q, int $limit = 20, int $page = 1, array $extraFilters = []): array
    {
        $query = array_merge(['q' => $q, 'limit' => $limit, 'page' => $page], $extraFilters);

        $path = '/api/usermgt/v1/users/search';
        $res = $this->request($path)->get($this->url($path), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('searchUsers', $res, ['query' => $query]);

        return [];
    }

    public function storeUser(array $payload, ?UploadedFile $photo = null): ?array
    {
        $path = '/api/usermgt/v1/users/store';

        $res = $photo
            ? $this->multipartRequest($path, $payload, $photo)->post($this->url($path))
            : $this->request($path)->post($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('storeUser', $res, ['payload' => $this->redactUser($payload)]);

        return null;
    }

    public function updateUser(int|string $id, array $payload, ?UploadedFile $photo = null): ?array
    {
        if ($photo) {
            $path = "/api/usermgt/v1/users/{$id}";
            $res = $this->multipartRequest($path, $payload + ['_method' => 'PUT'], $photo)
                ->post($this->url($path));
        } else {
            $path = "/api/usermgt/v1/users/{$id}";
            $res = $this->request($path)->put($this->url($path), $payload);
        }

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('updateUser', $res, ['id' => $id, 'payload' => $this->redactUser($payload)]);

        return null;
    }

    public function deleteUser(int|string $id): bool
    {
        $path = "/api/usermgt/v1/users/{$id}";
        $res = $this->request($path)->delete($this->url($path));

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return true;
        }

        $this->logFailure('deleteUser', $res, ['id' => $id]);

        return false;
    }

    public function setActive(int|string $id, bool $active): ?array
    {
        $path = "/api/usermgt/v1/users/{$id}/active";
        $res = $this->request($path)->patch($this->url($path), ['active' => $active]);

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('setActive', $res, ['id' => $id, 'active' => $active]);

        return null;
    }

    public function getUsersDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt, ['user_type', 'is_active', 'gender']);

        $path = '/api/usermgt/v1/users/list';
        $res = $this->request($path)->get($this->url($path), $query);

        return $res->json();
    }

    /**
     * --------------------------
     * Generic resource helpers
     * --------------------------
     *
     * These take a full path; the correct token (user vs service) is chosen automatically
     * from the path, so the Access Controls calls to /api/v1/internal/* just work:
     *
     *   $svc->listResource('/api/v1/internal/roles', 'listRoles')
     *   $svc->fetchResource("/api/v1/internal/users/{$id}/permissions", 'userPermissions')
     *   $svc->storeResource("/api/v1/internal/users/{$id}/access", $payload, 'saveAccess')
     */

    public function fetchResource(string $path, string $actionName, array $query = []): ?array
    {
        $res = $this->request($path)->get($this->url($path), $query);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);

        return null;
    }

    public function listResource(string $path, string $actionName, array $query = []): array
    {
        $res = $this->request($path)->get($this->url($path), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);

        return [];
    }

    public function storeResource(string $path, array $payload, string $actionName): ?array
    {
        $res = $this->request($path)->post($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redactUser($payload)]);

        return null;
    }

    public function updateResource(string $path, array $payload, string $actionName): ?array
    {
        $res = $this->request($path)->put($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redactUser($payload)]);

        return null;
    }

    public function deleteResource(string $path, string $actionName): bool
    {
        $res = $this->request($path)->delete($this->url($path));

        if ($res->successful()) {
            return true;
        }

        $this->logFailure($actionName, $res, ['path' => $path]);

        return false;
    }

    /**
     * --------------------------
     * Internals
     * --------------------------
     */

    /**
     * Shared JSON client. Pass the target $path so the correct token is chosen.
     */
    protected function request(string $path = '', ?int $timeoutSeconds = null): PendingRequest
    {
        return $this->baseRequest($path, $timeoutSeconds)->asJson();
    }

    protected function multipartRequest(string $path, array $fields, UploadedFile $photo): PendingRequest
    {
        $req = $this->baseRequest($path)->asMultipart();

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }
            $req = $req->attach($key, is_bool($value) ? (string) (int) $value : (string) $value);
        }

        return $req->attach(
            'attachments',
            fopen($photo->getRealPath(), 'r'),
            $photo->getClientOriginalName()
        );
    }

    /**
     * Common scaffolding. Chooses the token by path: SERVICE token for internal machine
     * endpoints, the USER token for everything else.
     */
    protected function baseRequest(string $path = '', ?int $timeoutSeconds = null): PendingRequest
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

        $req = Http::timeout($timeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->acceptJson()
            ->withHeaders($headers);

        return $req->withToken($this->tokenFor($path));
    }

    /**
     * Internal (machine) endpoints -> service token. Everything else -> the user's token.
     *
     * This is the fix for the 403: the Access Controls screen hits /api/v1/internal/*, which
     * ek-auth guards with EnsureClientToken (service tokens only). A user token there is
     * rejected by design.
     */
    protected function tokenFor(string $path): ?string
    {
        $normalised = '/' . ltrim($path, '/');

        foreach ($this->internalPrefixes as $prefix) {
            if (str_starts_with($normalised, $prefix)) {
                return $this->auth->serviceToken();
            }
        }

        return session('access_token');
    }

    protected function url(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return $this->baseUrl . $path;
    }

    protected function parseJson(Response $res): ?array
    {
        $json = $res->json();

        if (!is_array($json)) {
            return null;
        }

        if (array_key_exists('data', $json)) {
            return is_array($json['data']) ? $json['data'] : $json;
        }

        return $json;
    }

    protected function correlationId(): string
    {
        $existing = request()?->header('X-Correlation-ID');
        if (!empty($existing)) {
            return $existing;
        }

        return (string) Str::uuid();
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
            errorLogger(date('H') . '.error.log', 'USERMGT_CLIENT_ERROR::' . json_encode($payload));
        }

        Log::error('UserMgtService request failed', $payload);
    }

    protected function safeBody(Response $res): string
    {
        $body = (string) $res->body();

        if (strlen($body) > 5000) {
            return substr($body, 0, 5000) . '...<truncated>';
        }

        return $body;
    }

    protected function redactUser(array $payload): array
    {
        foreach (['password', 'passconf', 'password_confirmation'] as $secret) {
            if (array_key_exists($secret, $payload)) {
                $payload[$secret] = '***';
            }
        }

        return $payload;
    }

    protected function cacheKeyUser(int|string $id): string
    {
        return "usermgt:user:{$id}";
    }

    protected function forgetUserCache(int|string $id): void
    {
        Cache::forget($this->cacheKeyUser($id));
    }
}