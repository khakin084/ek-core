<?php

namespace Modules\Catalogs\Services;

use App\Services\Http\BaseMicroserviceClient;
use Illuminate\Support\Facades\Cache;

class CatalogService extends BaseMicroserviceClient
{
    protected string $defaultService = 'ek-catalog';

    // Short cache for by-id lookups (resolve selected dropdown IDs fast)
    protected int $itemCacheTtlSeconds = 600; // 10 minutes

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
            if (is_array($cached)) {
                return $cached;
            }
        }

        $query = $this->normalizeTypesFilter($types);
        $data = $this->fetchResource("/api/itemmaster/v1/items/get/{$id}", 'getItem', $query);

        if ($useCache && is_array($data)) {
            Cache::put($cacheKey, $data, $this->itemCacheTtlSeconds);
        }

        return $data;
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

        return $this->listResource('/api/items/search', 'searchItems', $query);
    }

    public function updateItem(int|string $id, array $payload): ?array
    {
        $data = $this->updateResource("/api/items/{$id}", $payload, 'updateItem');

        if ($data !== null) {
            $this->forgetItemCache($id);
        }

        return $data;
    }

    public function deleteItem(int|string $id): bool
    {
        $deleted = $this->deleteResource("/api/items/{$id}", 'deleteItem');

        if ($deleted) {
            $this->forgetItemCache($id);
        }

        return $deleted;
    }

    public function getItemsDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt, ['item_group_id', 'item_variety_id']);

        return $this->listResourceForDataTable('/api/itemmaster/v1/items/list', 'getItemsDataTable', $query);
    }

    public function getItemGroupsList(array $dt = []): array
    {
        $query = buildDtQuery($dt);

        return $this->listResourceForDataTable('/api/itemmaster/v1/item-groups/list', 'getItemGroupsList', $query);
    }

    public function getVarietiesList(array $dt = []): array
    {
        $query = buildDtQuery($dt);

        return $this->listResourceForDataTable('/api/itemmaster/v1/varieties/list', 'getVarietiesList', $query);
    }

    public function getItemItemVarietyParticulars(int|string $itemId): array
    {
        return $this->listResource("/api/items/{$itemId}/variety-particulars", 'getItemItemVarietyParticulars');
    }

    public function getItemComponents(int|string $id, array $types = []): array
    {
        $query = $this->normalizeTypesFilter($types);

        return $this->listResource("/api/items/{$id}/components", 'getItemComponents', $query);
    }

    public function retrieveUnitNLastPrice($item_id, $warehouse_id, $price_type): array
    {
        $query = [
            'warehouse_id' => $warehouse_id,
            'price_type' => $price_type,
        ];

        return $this->listResource("/api/items/{$item_id}/last-price", 'retrieveUnitNLastPrice', $query);
    }

    /**
     * --------------------------
     * Internals — only what the base class doesn't already provide.
     * --------------------------
     */

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
}