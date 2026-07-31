<?php

namespace Modules\UserMgt\Services;

use App\Helpers\ServiceDiscoveryHelper;
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

    // Short cache for by-id lookups (e.g. resolving a selected dropdown user fast)
    protected int $userCacheTtlSeconds = 600; // 10 minutes

    public function __construct(
        protected ServiceDiscoveryHelper $serviceDiscovery
    ) {
        $this->baseUrl = rtrim($this->serviceDiscovery->serviceUrl('ek-auth', ''), '/');
    }

    /**
     * --------------------------
     * Public API
     * --------------------------
     */

    /**
     * Fetch a single user by ID.
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

        $res = $this->request()
            ->get($this->url("/api/usermgt/v1/users/get/{$id}"));

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

    /**
     * Search users (best for dropdowns / typeahead).
     */
    public function searchUsers(
        string $q,
        int $limit = 20,
        int $page = 1,
        array $extraFilters = []
    ): array {
        $query = array_merge(
            ['q' => $q, 'limit' => $limit, 'page' => $page],
            $extraFilters
        );

        $res = $this->request()
            ->get($this->url('/api/usermgt/v1/users/search'), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('searchUsers', $res, ['query' => $query]);

        return [];
    }

    /**
     * Create a user in ek-auth.
     *
     * $photo is the uploaded "attachments" file from the form, if any.
     * When present we forward as multipart; otherwise a plain JSON POST.
     */
    public function storeUser(array $payload, ?UploadedFile $photo = null): ?array
    {
        $path = '/api/usermgt/v1/users/store';

        $res = $photo
            ? $this->multipartRequest($payload, $photo)->post($this->url($path))
            : $this->request()->post($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('storeUser', $res, ['payload' => $this->redactUser($payload)]);

        return null;
    }

    /**
     * Update a user in ek-auth.
     */
    public function updateUser(int|string $id, array $payload, ?UploadedFile $photo = null): ?array
    {
        // Multipart + PUT is awkward across HTTP clients, so tunnel the method.
        if ($photo) {
            $res = $this->multipartRequest($payload + ['_method' => 'PUT'], $photo)
                ->post($this->url("/api/usermgt/v1/users/{$id}"));
        } else {
            $res = $this->request()
                ->put($this->url("/api/usermgt/v1/users/{$id}"), $payload);
        }

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('updateUser', $res, [
            'id' => $id,
            'payload' => $this->redactUser($payload),
        ]);

        return null;
    }

    public function deleteUser(int|string $id): bool
    {
        $res = $this->request()
            ->delete($this->url("/api/usermgt/v1/users/{$id}"));

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return true;
        }

        $this->logFailure('deleteUser', $res, ['id' => $id]);

        return false;
    }

    /**
     * Toggle / set active status (handy for the switch in your form/list).
     */
    public function setActive(int|string $id, bool $active): ?array
    {
        $res = $this->request()
            ->patch($this->url("/api/usermgt/v1/users/{$id}/active"), ['active' => $active]);

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('setActive', $res, ['id' => $id, 'active' => $active]);

        return null;
    }

    /**
     * DataTable endpoint for the users list.
     */
    public function getUsersDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt, ['user_type', 'is_active', 'gender']);

        $res = $this->request()->get(
            $this->url('/api/usermgt/v1/users/list'),
            $query
        );

        return $res->json();
    }

    /**
     * --------------------------
     * Generic resource helpers
     * --------------------------
     *
     * Controllers can call these directly with a full path (relative to the
     * ek-auth base URL) instead of adding one-off wrappers, e.g.:
     *
     *   $auth->fetchResource('/api/usermgt/v1/roles/get/' . $id, 'getRole')
     *   $auth->storeResource('/api/usermgt/v1/roles/store', $payload, 'storeRole')
     *   $auth->listResource('/api/usermgt/v1/permissions', 'listPermissions', $query)
     *   $auth->updateResource('/api/usermgt/v1/roles/' . $id, $payload, 'updateRole')
     *   $auth->deleteResource('/api/usermgt/v1/roles/' . $id, 'deleteRole')
     */

    public function fetchResource(string $path, string $actionName, array $query = []): ?array
    {
        $res = $this->request()->get($this->url($path), $query);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);

        return null;
    }

    public function listResource(string $path, string $actionName, array $query = []): array
    {
        $res = $this->request()->get($this->url($path), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'query' => $query]);

        return [];
    }

    public function storeResource(string $path, array $payload, string $actionName): ?array
    {
        $res = $this->request()->post($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redactUser($payload)]);

        return null;
    }

    public function updateResource(string $path, array $payload, string $actionName): ?array
    {
        $res = $this->request()->put($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure($actionName, $res, ['path' => $path, 'payload' => $this->redactUser($payload)]);

        return null;
    }

    public function deleteResource(string $path, string $actionName): bool
    {
        $res = $this->request()->delete($this->url($path));

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
     * Shared HTTP client (JSON): correlation id, token + audit propagation,
     * retries and timeout.
     */
    protected function request(?int $timeoutSeconds = null): PendingRequest
    {
        return $this->baseRequest($timeoutSeconds)->asJson();
    }

    /**
     * Multipart variant for file uploads. Same headers/token/retries, but the
     * body is sent as multipart/form-data with the photo attached.
     */
    protected function multipartRequest(array $fields, UploadedFile $photo): PendingRequest
    {
        $req = $this->baseRequest()->asMultipart();

        // Flatten scalar fields; skip nulls so we don't send "null" strings.
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
     * Common request scaffolding shared by JSON and multipart clients.
     */
    protected function baseRequest(?int $timeoutSeconds = null): PendingRequest
    {
        $timeout = $timeoutSeconds ?? $this->timeoutSeconds;

        $headers = [
            'X-Correlation-ID' => $this->correlationId(),
        ];

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

        $accessToken = session('access_token');
        if (!empty($accessToken)) {
            $req = $req->withToken($accessToken);
        }

        return $req;
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

    /**
     * Redact sensitive fields before logging user payloads.
     */
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