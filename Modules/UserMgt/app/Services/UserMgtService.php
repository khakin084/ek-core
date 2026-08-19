<?php

namespace Modules\UserMgt\Services;

use App\Services\Http\BaseMicroserviceClient;
use App\Services\Http\TokenType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class UserMgtService extends BaseMicroserviceClient
{
    protected string $defaultService = 'ek-auth';

    protected int $userCacheTtlSeconds = 600; // 10 minutes

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

        $data = $this->fetchResource("/api/usermgt/v1/users/get/{$id}", 'getUser');

        if ($useCache && is_array($data)) {
            Cache::put($cacheKey, $data, $this->userCacheTtlSeconds);
        }

        return $data;
    }

    public function searchUsers(string $q, int $limit = 20, int $page = 1, array $extraFilters = []): array
    {
        $query = array_merge(['q' => $q, 'limit' => $limit, 'page' => $page], $extraFilters);

        return $this->listResource('/api/usermgt/v1/users/search', 'searchUsers', $query);
    }

    public function storeUser(array $payload, ?UploadedFile $photo = null): ?array
    {
        $path = '/api/usermgt/v1/users/store';

        $res = $photo
            ? $this->multipartRequest($path, $payload, $photo)->post($this->url($path))
            : $this->userRequest($path)->asJson()->post($this->url($path), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('storeUser', $res, ['payload' => $this->redact($payload)]);

        return null;
    }

    public function updateUser(int|string $id, array $payload, ?UploadedFile $photo = null): ?array
    {
        $path = "/api/usermgt/v1/users/{$id}";

        $res = $photo
            ? $this->multipartRequest($path, $payload + ['_method' => 'PUT'], $photo)->post($this->url($path))
            : $this->userRequest($path)->asJson()->put($this->url($path), $payload);

        if ($res->successful()) {
            $this->forgetUserCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('updateUser', $res, ['id' => $id, 'payload' => $this->redact($payload)]);

        return null;
    }

    public function deleteUser(int|string $id): bool
    {
        $deleted = $this->deleteResource("/api/usermgt/v1/users/{$id}", 'deleteUser');

        if ($deleted) {
            $this->forgetUserCache($id);
        }

        return $deleted;
    }

    public function setActive(int|string $id, bool $active): ?array
    {
        $path = "/api/usermgt/v1/users/{$id}/active";
        $res = $this->userRequest($path)->asJson()->patch($this->url($path), ['active' => $active]);

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

        return $this->listResourceForDataTable('/api/usermgt/v1/users/list', 'getUsersDataTable', $query);
    }

    public function getRolesDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt);

        return $this->listResourceForDataTable('/api/usermgt/v1/roles/list', 'getRolesDataTable', $query);
    }

    /**
     * --------------------------
     * Internals — only what the base class doesn't already provide.
     * --------------------------
     */

    protected function multipartRequest(string $path, array $fields, UploadedFile $photo): PendingRequest
    {
        $req = $this->userRequest($path)->asMultipart();

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

    protected function cacheKeyUser(int|string $id): string
    {
        return "usermgt:user:{$id}";
    }

    protected function forgetUserCache(int|string $id): void
    {
        Cache::forget($this->cacheKeyUser($id));
    }
}