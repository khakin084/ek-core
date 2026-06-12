<?php

namespace Modules\Catalogs\Services;

use App\Helpers\ServiceDiscoveryHelper;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CatalogService
{
    protected string $baseUrl;

    // Reasonable defaults; tweak as you wish
    protected int $timeoutSeconds = 6;
    protected int $retryTimes = 2;
    protected int $retrySleepMs = 150;

    // Optional: short cache for by-id lookups (resolve selected dropdown IDs fast)
    protected int $itemCacheTtlSeconds = 600; // 10 minutes

    public function __construct(
        protected ServiceDiscoveryHelper $serviceDiscovery
    ) {
        $this->baseUrl = rtrim($this->serviceDiscovery->serviceUrl('ek-catalog', ''), '/');
    }

    /**
     * --------------------------
     * Public API
     * --------------------------
     */

    /**
     * Fetch a single item by its ID.
     * Optionally pass $types as string or array to enforce a type constraint.
     *
     * Examples:
     *  getItem(5)
     *  getItem(5, ['CONSUMABLE','COMPOSITE'])
     */
    public function getItem(int|string $id, string|array|null $types = null, bool $useCache = true): ?array
    {
        $cacheKey = $this->cacheKeyItem($id, $types);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached))
                return $cached;
        }

        $query = $this->normalizeTypesFilter($types);

        $res = $this->request()
            ->get($this->url("/api/itemmaster/v1/items/get/{$id}"), $query);

        if ($res->successful()) {
            $data = $this->parseJson($res);

            if ($useCache && is_array($data)) {
                Cache::put($cacheKey, $data, $this->itemCacheTtlSeconds);
            }

            return $data;
        }

        $this->logFailure('getItem', $res, [
            'id' => $id,
            'types' => $types,
            'query' => $query,
        ]);

        return null;
    }

    /**
     * Search items (best for dropdowns/typeahead).
     * Assumes ek-catalog supports:
     *  GET /api/items/search?q=...&type[]=...&limit=...&page=...
     */
    public function searchItems(
        string $q,
        string|array|null $types = null,
        int $limit = 20,
        int $page = 1,
        array $extraFilters = []
    ): array {
        $query = array_merge(
            ['q' => $q, 'limit' => $limit, 'page' => $page],
            $this->normalizeTypesFilter($types),
            $extraFilters
        );

        $res = $this->request()
            ->get($this->url('/api/items/search'), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('searchItems', $res, ['query' => $query]);

        return [];
    }

    /**
     * List items (use carefully; can be large).
     * Prefer searchItems() for dropdowns.
     */
    public function listItems(array $query = []): array
    {
        $res = $this->request()
            ->get($this->url('/api/items'), $query);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('listItems', $res, ['query' => $query]);
        return [];
    }

    /**
     * Create item.
     */
    public function createItem(array $payload): ?array
    {
        $res = $this->request()
            ->post($this->url('/api/items'), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('createItem', $res, ['payload' => $this->redact($payload)]);
        return null;
    }

    /**
     * Update item.
     */
    public function updateItem(int|string $id, array $payload): ?array
    {
        $res = $this->request()
            ->put($this->url("/api/items/{$id}"), $payload);

        if ($res->successful()) {
            // Invalidate by-id cache (all variants)
            $this->forgetItemCache($id);
            return $this->parseJson($res);
        }

        $this->logFailure('updateItem', $res, [
            'id' => $id,
            'payload' => $this->redact($payload),
        ]);

        return null;
    }

    public function deleteItem(int|string $id): bool
    {
        $res = $this->request()
            ->delete($this->url("/api/items/{$id}"));

        if ($res->successful()) {
            // Invalidate by-id cache (all variants)
            $this->forgetItemCache($id);
            return true;
        }

        $this->logFailure('deleteItem', $res, ['id' => $id]);
        return false;
    }

    /**
     * DataTable endpoint (your existing method, cleaned).
     */
    public function getItemsDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt,['item_group_id', 'item_variety_id']);

        $res = $this->request()->get(
            $this->url("/api/itemmaster/v1/items/list"),
            $query
        );

        return $res->json();
    }

    /**
     * --------------------------
     * Internals
     * --------------------------
     */

    /**
     * Build HTTP client with shared concerns:
     * - correlation id propagation
     * - bearer token propagation (optional)
     * - retries / timeout
     */
    protected function request(?int $timeoutSeconds = null): PendingRequest
    {
        $timeout = $timeoutSeconds ?? $this->timeoutSeconds;
        $correlationId = $this->correlationId();

        $headers = [
            'X-Correlation-ID' => $correlationId,
        ];

        // Forward audit headers if we are in an HTTP request context
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
            ->asJson()
            ->withHeaders($headers);

        $accessToken = session('access_token');
        if (!empty($accessToken)) {
            $req = $req->withToken($accessToken);
        }

        return $req;
    }

    protected function url(string $path): string
    {
        // Ensure exactly one slash between base and path
        $path = '/' . ltrim($path, '/');
        return $this->baseUrl . $path;
    }

    /**
     * Parse response JSON:
     * - if payload has "data" key, return it
     * - else return entire JSON
     */
    protected function parseJson(Response $res): ?array
    {
        $json = $res->json();

        if (!is_array($json)) {
            return null;
        }

        // common API style: { data: [...] }
        if (array_key_exists('data', $json)) {
            return is_array($json['data']) ? $json['data'] : $json;
        }

        return $json;
    }

    /**
     * Turn types into query params:
     * - string => type=...
     * - array => type[]=...&type[]=...
     */
    protected function normalizeTypesFilter(string|array|null $types): array
    {
        if ($types === null || $types === '') {
            return [];
        }

        if (is_array($types)) {
            $types = array_values(array_filter($types, fn($t) => $t !== null && $t !== ''));
            return empty($types) ? [] : ['type' => $types];
        }

        return ['type' => $types];
    }

    protected function correlationId(): string
    {
        // If your app already sets one earlier in middleware, reuse it
        $existing = request()?->header('X-Correlation-ID');
        if (!empty($existing))
            return $existing;

        // fallback: stable per request
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

        // Your existing helper (if present)
        if (function_exists('errorLogger')) {
            errorLogger(date('H') . '.error.log', 'CATALOG_CLIENT_ERROR::' . json_encode($payload));
        }

        Log::error('CatalogService request failed', $payload);
    }

    protected function safeBody(Response $res): string
    {
        $body = (string) $res->body();

        // Avoid logging massive responses
        if (strlen($body) > 5000) {
            return substr($body, 0, 5000) . '...<truncated>';
        }

        return $body;
    }

    /**
     * Redact common sensitive fields from payload logs.
     */
    protected function redact(array $payload): array
    {
        $sensitive = ['password', 'secret', 'token', 'access_token', 'refresh_token'];

        foreach ($sensitive as $k) {
            if (array_key_exists($k, $payload)) {
                $payload[$k] = '***';
            }
        }

        return $payload;
    }

    /**
     * Cache key helpers
     */
    protected function cacheKeyItem(int|string $id, string|array|null $types): string
    {
        $suffix = $types === null ? 'any' : substr(hash('sha256', json_encode($types)), 0, 10);
        return "catalog:item:{$id}:{$suffix}";
    }

    protected function forgetItemCache(int|string $id): void
    {
        // If you need full wildcard invalidation, use rev keys instead.
        // For now, simplest: just forget "any".
        Cache::forget("catalog:item:{$id}:any");
    }

    public function getItemItemVarietyParticulars(int|string $itemId): array
    {
        $res = $this->request()
            ->get($this->url("/api/items/{$itemId}/variety-particulars"));

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('getItemItemVarietyParticulars', $res, ['itemId' => $itemId]);

        return [];
    }

    public function getItemComponents(int|string $id, array $types = []): array
    {
        $res = $this->request()
            ->get($this->url("/api/items/{$id}/components"), [
                'query' => $this->normalizeTypesFilter($types)
            ]);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('getItemComponents', $res, ['id' => $id]);

        return [];
    }

    public function getInventoryDetail(int|string $itemId): ?array
    {
        $res = $this->request()
            ->get($this->url("/api/items/{$itemId}/inventory-detail"));

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('getInventoryDetail', $res, ['itemId' => $itemId]);

        return null;
    }

    public function getItemGroup(int|string|null $id = null): ?array
    {
        $res = $this->request()->get($this->url("/api/itemmaster/v1/item-groups/get/{$id}"));

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('getItemGroup', $res, ['id' => $id]);

        return null;
    }

    public function getItemGroupsList(array $dt = []): array
    {
        $query = buildDtQuery($dt);

        $res = $this->request()->get(
            $this->url("/api/itemmaster/v1/item-groups/list"),
            $query
        );

        return $res->json();
    }

    public function storeItemGroup(array $payload): ?array
    {
        $res = $this->request()
            ->post($this->url("/api/itemmaster/v1/item-groups/store"), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('createItemGroup', $res, [
            'payload' => $this->redact($payload),
        ]);

        return null;
    }

    public function deleteItemGroup(int|string $id): bool
    {
        $res = $this->request()
            ->delete($this->url("/api/item-groups/{$id}"));

        if ($res->successful()) {
            return true;
        }

        $this->logFailure('deleteItemGroup', $res, ['id' => $id]);

        return false;
    }

    public function getVarietiesList(array $dt = [])
    {
        $query = buildDtQuery($dt);

        $res = $this->request()->get(
            $this->url("/api/itemmaster/v1/varieties/list"),
            $query
        );

        return $res->json();
    }

    public function createVariety(array $payload): ?array
    {
        $res = $this->request()
            ->post($this->url("/api/itemmaster/v1/varieties/store"), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('createVariety', $res, [
            'payload' => $this->redact($payload),
        ]);

        return null;
    }

    public function deleteVariety(int|string $id): bool
    {
        $res = $this->request()
            ->delete($this->url("/api/varieties/{$id}"));

        if ($res->successful()) {
            return true;
        }

        $this->logFailure('deleteVariety', $res, ['id' => $id]);

        return false;
    }

    public function retrieveUnitNLastPrice($item_id, $warehouse_id, $price_type): array
    {
        $res = $this->request()
            ->get($this->url("/api/items/{$item_id}/last-price"), [
                'warehouse_id' => $warehouse_id,
                'price_type' => $price_type,
            ]);

        if ($res->successful()) {
            return $this->parseJson($res) ?? [];
        }

        $this->logFailure('retrieveUnitNLastPrice', $res, [
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'price_type' => $price_type,
        ]);

        return [];
    }

    public function getItemVarietyParticular(int|string|null $id = null): ?array
    {
        $res = $this->request()->get($this->url("/api/itemmaster/v1/variety-particulars/get/{$id}"));

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('getItemVarietyParticular', $res, ['id' => $id]);

        return null;
    }

    public function createVarietyParticular(array $payload): ?array
    {
        $res = $this->request()
            ->post($this->url("/api/itemmaster/v1/variety-particulars/store"), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('createVarietyParticular', $res, [
            'payload' => $this->redact($payload),
        ]);

        return null;
    }

    public function getUnit(int|string|null $id = null): ?array
    {
        $res = $this->request()->get($this->url("/api/itemmaster/v1/item-groups/get/{$id}"));

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('getItemGroup', $res, ['id' => $id]);

        return null;
    }

    public function createUnit(array $payload): ?array
    {
        $res = $this->request()
            ->post($this->url("/api/itemmaster/v1/units/store"), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('createUnit', $res, [
            'payload' => $this->redact($payload),
        ]);

        return null;
    } 
    
    public function getIivps(int|string|null $id = null): ?array
    {
        $res = $this->request()->get($this->url("/api/itemmaster/v1/variety-particulars/get-iivps/{$id}"));

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('getIivps', $res, ['id' => null]);

        return null;
    }
}