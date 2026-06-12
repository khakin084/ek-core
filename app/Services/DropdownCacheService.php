<?php

namespace App\Services;

use App\Helpers\ServiceDiscoveryHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropdownCacheService
{
    public function __construct(
        private ServiceDiscoveryHelper $serviceDiscoveryHelper
    ) {
    }

    protected int $defaultTtl = 3600;        // 1 hour
    protected int $staleTtl = 86400;         // 24 hours fallback
    protected int $httpTimeoutSeconds = 5;
    protected int $lockTimeoutSeconds = 30;   // Increased from 10

    /**
     * Dropdown source configuration.
     */
    public function sources(): array
    {
        return [
            'item_groups' => [
                'url' => $this->serviceDiscoveryHelper->serviceUrl('ek-catalog', '/api/itemmaster/v1/item-groups/dropdown'),
                'value' => 'id',
                'label' => ['name'],
                'meta' => ['name'],
            ],

            'users' => [
                'url' => $this->serviceDiscoveryHelper->serviceUrl('ek-auth', '/api/v1/users/dropdown'),
                'value' => 'id',
                'label' => ['name', 'email'],
                'meta' => ['email'],
            ],

            'items' => [
                'url' => $this->serviceDiscoveryHelper->serviceUrl('ek-catalog', '/api/itemmaster/v1/items/dropdown'),
                'value' => 'id',
                'label' => ['name'],
                'append' => ['sku' => ' (SKU: {sku})'],
                'meta' => ['sku', 'status', 'type'],
                'filter_param' => 'type',
            ],

            'units' => [
                'url' => $this->serviceDiscoveryHelper->serviceUrl('ek-catalog', '/api/itemmaster/v1/units/dropdown'),
                'value' => 'id',
                'label' => ['symbol'],
                'meta' => ['symbol'],
            ],

            'item_variety_particulars' => [
                'url' => $this->serviceDiscoveryHelper->serviceUrl('ek-catalog', '/api/itemmaster/v1/variety-particulars/dropdown'),
                'value' => 'id',
                'label' => ['name'],
                'meta' => ['name'],
            ],
        ];
    }

    /**
     * Get dropdown data.
     *
     * @param string $key
     * @param array $filters
     * @param int|null $ttl
     * @param mixed $default
     * @return array
     */
    public function get(string $key, array $filters = [], ?int $ttl = null, $default = []): array
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $rev = (int) Cache::get($this->revKey($key), 1);
        $filtersHash = $this->filtersHash($key, $filters);
        $cacheKey = $this->cacheKey($key, $rev, $filtersHash);
        $staleKey = $this->staleKey($key, $filtersHash);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey, $default);
        }

        $lock = Cache::lock("lock:dropdown:{$key}:{$filtersHash}", $this->lockTimeoutSeconds);

        try {
            if ($lock->get()) {
                if (Cache::has($cacheKey)) {
                    return Cache::get($cacheKey, $default);
                }

                $data = $this->fetchFromSource($key, $filters);

                if (is_array($data)) {
                    Cache::put($cacheKey, $data, $ttl);
                    Cache::put($staleKey, $data, $this->staleTtl);
                    return $data;
                }

                return Cache::get($staleKey, $default);
            }

            return Cache::get($staleKey, $default);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Force refresh specific dropdown + filter set.
     *
     * @param string $key
     * @param array $filters
     * @param int|null $ttl
     * @return bool
     */
    public function warm(string $key, array $filters = [], ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;

        $data = $this->fetchFromSource($key, $filters);
        if (!is_array($data)) {
            return false;
        }

        $rev = (int) Cache::get($this->revKey($key), 1);
        $filtersHash = $this->filtersHash($key, $filters);

        Cache::put($this->cacheKey($key, $rev, $filtersHash), $data, $ttl);
        Cache::put($this->staleKey($key, $filtersHash), $data, $this->staleTtl);

        return true;
    }

    /**
     * Event-driven invalidation: bump revision so ALL filter variants are invalidated.
     *
     * @param string $key
     * @return void
     */
    public function invalidate(string $key): void
    {
        Cache::increment($this->revKey($key));
    }

    // ---------------- Fetching ----------------

    /**
     * @param string $key
     * @param array $filters
     * @return array|null
     */
    protected function fetchFromSource(string $key, array $filters = []): ?array
    {
        $sources = $this->sources();

        if (!isset($sources[$key])) {
            errorLogger('error.log', "Dropdown source not configured" . json_encode(['key' => $key]));
            return null;
        }

        $cfg = $sources[$key];
        $url = is_array($cfg) ? ($cfg['url'] ?? null) : $cfg;

        if (!$url) {
            errorLogger('error.log', "Dropdown URL missing" . json_encode(['key' => $key]));
            return null;
        }

        $query = $this->buildQueryParams($key, $filters);

        try {
            $response = $this->buildHttpRequest()
                ->get($url, $query);

            if (!$response->successful()) {
                errorLogger('error.log', "Dropdown fetch failed" . json_encode([
                    'key' => $key,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $this->truncate($response->body()),
                    'correlation_id' => $this->correlationId(),
                ]));

                return null;
            }

            $json = $response->json();

            $raw = is_array($json) && array_key_exists('data', $json)
                ? $json['data']
                : $json;

            if (!is_array($raw)) {
                errorLogger('error.log', "Dropdown response malformed" . json_encode([
                    'key' => $key,
                    'url' => $url,
                ]));
                return null;
            }

            return $this->transformDynamic($key, $raw);

        } catch (\Throwable $e) {
            errorLogger('error.log', "Exception while fetching dropdown data" . json_encode([
                'key' => $key,
                'url' => $url,
                'query' => $query,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId(),
            ]));

            return null;
        }
    }

    /**
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function buildHttpRequest()
    {
        $request = Http::timeout($this->httpTimeoutSeconds)
            ->retry(2, 150)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Correlation-ID' => $this->correlationId(),
                'X-Service-Name' => config('app.name', 'ek-core'),
            ]);

        // Forward user session token if exists and not empty
        $accessToken = session('access_token');
        if (!empty($accessToken)) {
            $request = $request->withToken($accessToken);
        }
        // Fallback service token only if it's actually configured
        elseif (!empty(config('services.internal.token'))) {
            $request = $request->withToken(config('services.internal.token'));
        }

        // Propagate user id only when a valid authenticated user exists
        if (auth()->check() && ($userId = authUserId()) !== null) {
            $request = $request->withHeaders([
                'X-Requested-By' => (string) $userId,
            ]);
        }

        return $request;
    }

    /**
     * @return string
     */
    protected function correlationId(): string
    {
        return request()?->header('X-Correlation-ID')
            ?? request()?->attributes->get('correlation_id')
            ?? \Illuminate\Support\Str::uuid()->toString();
    }

    /**
     * @param string $body
     * @param int $limit
     * @return string
     */
    protected function truncate(string $body, int $limit = 4000): string
    {
        return strlen($body) > $limit
            ? substr($body, 0, $limit) . '...<truncated>'
            : $body;
    }

    /**
     * Convert filters argument into query params.
     *
     * @param string $key
     * @param array $filters
     * @return array
     */
    protected function buildQueryParams(string $key, array $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        if ($this->isAssoc($filters)) {
            return $filters;
        }

        $cfg = $this->sources()[$key] ?? [];
        $param = is_array($cfg) && isset($cfg['filter_param']) ? (string) $cfg['filter_param'] : 'type';

        return [$param => array_values($filters)];
    }

    /**
     * @param array $arr
     * @return bool
     */
    protected function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    // ---------------- Cache keys ----------------

    /**
     * @param string $key
     * @return string
     */
    protected function revKey(string $key): string
    {
        return "dropdown:{$key}:rev";
    }

    /**
     * @param string $key
     * @param int $rev
     * @param string $filtersHash
     * @return string
     */
    protected function cacheKey(string $key, int $rev, string $filtersHash): string
    {
        return "dropdown:{$key}:v{$rev}:{$filtersHash}";
    }

    /**
     * @param string $key
     * @param string $filtersHash
     * @return string
     */
    protected function staleKey(string $key, string $filtersHash): string
    {
        return "dropdown:{$key}:stale:{$filtersHash}";
    }

    /**
     * @param string $key
     * @param array $filters
     * @return string
     */
    protected function filtersHash(string $key, array $filters): string
    {
        if (empty($filters)) {
            return 'nofilter';
        }

        if ($this->isAssoc($filters)) {
            ksort($filters);
            foreach ($filters as $k => $v) {
                if (is_array($v)) {
                    sort($v);
                    $filters[$k] = $v;
                }
            }
        } else {
            sort($filters);
        }

        return substr(hash('sha256', $key . '|' . json_encode($filters)), 0, 16);
    }

    // ---------------- Dynamic transform ----------------

    /**
     * @param string $key
     * @param array $rows
     * @return array
     */
    protected function transformDynamic(string $key, array $rows): array
    {
        $cfg = $this->sources()[$key] ?? null;

        if (!is_array($cfg)) {
            return $this->defaultTransform($rows);
        }

        $valueField = $cfg['value'] ?? 'id';
        $labelFields = (array) ($cfg['label'] ?? ['name', 'title']);
        $metaFields = (array) ($cfg['meta'] ?? []);
        $appendRules = (array) ($cfg['append'] ?? []);

        $out = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $value = $row[$valueField] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $label = $this->firstNonEmpty($row, $labelFields);

            foreach ($appendRules as $field => $pattern) {
                $v = $row[$field] ?? null;
                if ($v !== null && (string) $v !== '') {
                    $appended = $this->interpolate($pattern, $row);
                    if ($appended !== '') {
                        $label .= $appended;
                    }
                }
            }

            $meta = [];
            foreach ($metaFields as $mf) {
                $meta[$mf] = $row[$mf] ?? null;
            }

            $out[] = [
                'value' => $value,
                'label' => $label !== '' ? $label : (string) $value,
                'meta' => array_filter($meta, fn($v) => $v !== null),
            ];
        }

        return $out;
    }

    /**
     * @param array $rows
     * @return array
     */
    protected function defaultTransform(array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            $value = $row['id'] ?? $row['value'] ?? null;
            if ($value === null) {
                return null;
            }

            $label = $row['name'] ?? $row['label'] ?? $row['title'] ?? (string) $value;

            return ['value' => $value, 'label' => $label, 'meta' => []];
        }, $rows)));
    }

    /**
     * @param array $row
     * @param array $fields
     * @return string
     */
    protected function firstNonEmpty(array $row, array $fields): string
    {
        foreach ($fields as $f) {
            $v = $row[$f] ?? null;
            // Only use scalar values (avoid casting arrays/objects to "Array")
            if ($v !== null && is_scalar($v) && trim((string) $v) !== '') {
                return (string) $v;
            }
        }
        return '';
    }

    /**
     * @param string $pattern
     * @param array $row
     * @return string
     */
    protected function interpolate(string $pattern, array $row): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($m) use ($row) {
            $v = $row[$m[1]] ?? '';
            return $v === null ? '' : (string) $v;
        }, $pattern);
    }
}