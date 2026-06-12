<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('encodeUrlx')) {
    function encodeUrlx($data): string
    {
        $cipher = env('OPENSSL_ENCRYPTION_ALGORITHM', 'aes-128-cbc');
        $key = bin2hex(env('APP_KEY', 'base64:rF2vjKpL9mQ7wXyZ3aBc5dEf8gH1iJ0kN4oPqRsTuVw='));

        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        global $tag;
        $cipherData = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        $encoded = base64_encode($iv . $cipherData);
        // Replacing some characters which may Result from Encoding
        $encodedModified = strtr($encoded, '+/=', '-_,');
        return $encodedModified;
    }
}

if (!function_exists('decodeUrlx')) {
    function decodeUrlx($data): false|string
    {
        // Check for Invalid Formats and data
        if ($data == null || strlen($data) <= 16) {
            redirect()->back();
        }

        $cipher = env('OPENSSL_ENCRYPTION_ALGORITHM', 'aes-128-cbc');
        $key = bin2hex(env('APP_KEY', 'base64:rF2vjKpL9mQ7wXyZ3aBc5dEf8gH1iJ0kN4oPqRsTuVw='));

        $encodedModified = strtr($data, '-_,', '+/=');
        $decoded = base64_decode($encodedModified);

        $encrypted = substr($decoded, 16);
        $iv = substr($decoded, 0, 16);

        $original_data = openssl_decrypt("$encrypted", $cipher, $key, OPENSSL_RAW_DATA, $iv);

        return $original_data;
    }
}


if (!function_exists('infoLogger')) {
    function infoLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'info.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path)
        ])->info($message);
    }
}

if (!function_exists('errorLogger')) {
    function errorLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'error.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path),
        ])->error($message);
    }
}

if (!function_exists('debugLogger')) {
    function debugLogger(string|null $path = null, string|null $message = null): void
    {
        $path = $path ?? 'debug.log';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/' . date('Y') . "/" . date('F') . "/" . date('Y-m-d') . "/" . getLoggedClass($trace) . '/' . getLoggedMethod($trace) . '/' . $path),
        ])->debug($message);
    }
}

function getLoggedClass(array $trace)
{
    return \Illuminate\Support\Arr::last(explode("\\", $trace[1]['class'])) ?? null;
}

function getLoggedMethod(array $trace)
{
    return $trace[1]['function'] ?? null;
}


if (!function_exists('authUserId')) {
    function authUserId()
    {
        if (request()->has('userId')) {
            return json_decode(request()->input('userId'), true)['id'];
        }
        return auth()->id();
    }
}

if (!function_exists('authUser')) {
    function authUser(): \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null
    {
        $userSession = request()->input('userId');
        if (!empty($userSession)) {
            return new \App\Models\User(json_decode($userSession, true));
        }
        return auth()->user();
    }
}

if (!function_exists('authUserPermissions')) {
    function authUserPermissions(): array
    {
        if (request()->has('permissions')) {
            $permissions = [];
            $scopes = request()->input('permissions');
            if (count($scopes) > 0) {
                foreach ($scopes as $permission) {
                    if (str_contains($permission, ':') && substr($permission, 0, strpos($permission, ':')) === 'core') {
                        $permissions[] = substr($permission, strpos($permission, ':') + 1);
                    }
                }
            }

            return $permissions;
        }
        return [];
    }
}

if (!function_exists('id')) {
    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function id()
    {
        return authUser()->id;
    }
}

if (!function_exists('userObject')) {
    function userObject(): false|string|null
    {
        $user = request()->input('userId');
        if (!empty($user)) {
            $userData = json_decode($user, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode([
                    'id' => $userData['id'] ?? null,
                    'ward_id' => $userData['ward_id'] ?? null,
                    'district_id' => $userData['district_id'] ?? null,
                    'name' => $userData['name'] ?? null,
                    'email' => $userData['email'] ?? null,
                    'full_name' => $userData['full_name'] ?? null,
                    'username' => $userData['username'] ?? null,
                    'phone' => $userData['phone'] ?? null,
                    'user_type' => $userData['user_type'] ?? null,
                    'userable_type' => $userData['userable_type'] ?? null,
                    'userable_id' => $userData['userable_id'] ?? null,
                    'gender' => $userData['gender'] ?? null,
                    'is_active' => $userData['is_active'] ?? null,
                    'change_password' => $userData['change_password'] ?? null,
                ]);
            }
        }
        return null;
    }
}

if (!function_exists('isMultidimensional')) {
    function isMultidimensional(array $array): bool
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('getModule')) {
    /**
     * Get module by name
     * 
     * @param string $moduleName
     * @return \App\Models\Module|null
     */
    function getModule($moduleName)
    {
        return DB::table('modules')
            ->where('name', $moduleName)
            ->first();
    }
}

if (!function_exists('dtParams')) {
    /**
     * Universal DataTables params extractor (BFF + downstream).
     *
     * Supports BOTH:
     *  - DataTables native search[value]
     *  - Custom flattened search
     *
     * @param Request $request
     * @param array $extraKeys   Additional filter keys to include (e.g. ['user_filter','created_from'])
     * @param array $defaults    Defaults for extra keys
     * @param array $options     Options:
     *   - 'min_length' => int (default 1)
     *   - 'max_length' => int|null (default null)
     *   - 'include_order_columns' => bool (default true)
     *   - 'drop_empty' => bool (default true)
     * @return array
     */
    function dtParams(
        Request $request,
        array $extraKeys = [],
        array $defaults = [],
        array $options = []
    ): array {

        $minLength = (int) ($options['min_length'] ?? 1);
        $maxLength = $options['max_length'] ?? null;
        $includeOrderColumns = (bool) ($options['include_order_columns'] ?? true);
        $dropEmpty = (bool) ($options['drop_empty'] ?? true);

        $rawSearch = $request->input('search');

        if (is_array($rawSearch)) {
            $search = $rawSearch['value'] ?? '';
        } elseif (is_string($rawSearch)) {
            $search = $rawSearch;
        } else {
            $search = '';
        }

        $length = (int) $request->input('length', 10);
        $length = max($minLength, $length);
        if ($maxLength !== null) {
            $length = min((int) $maxLength, $length);
        }

        $params = [
            'draw' => (int) $request->input('draw', 1),
            'start' => max(0, (int) $request->input('start', 0)),
            'length' => $length,
            'search' => trim($search),
        ];

        if ($includeOrderColumns) {
            $params['order'] = $request->input('order', []);
            $params['columns'] = $request->input('columns', []);
        }

        foreach ($extraKeys as $key) {
            $val = $request->input($key, $defaults[$key] ?? null);

            if ($dropEmpty) {
                if ($val !== null && $val !== '') {
                    $params[$key] = $val;
                }
            } else {
                $params[$key] = $val;
            }
        }

        return $params;
    }
}

if (!function_exists('buildDtQuery')) {

    function buildDtQuery(array $dt, array $extraFilters = []): array
    {
        // Core DataTables keys
        $query = [
            'draw' => (int) ($dt['draw'] ?? 1),
            'start' => (int) ($dt['start'] ?? 0),
            'length' => (int) ($dt['length'] ?? 10),
            'search' => (string) ($dt['search'] ?? ''),
            'order' => $dt['order'] ?? [],
            'columns' => $dt['columns'] ?? [],
        ];

        // Forward only specified extra filters
        foreach ($extraFilters as $key) {
            if (array_key_exists($key, $dt) && $dt[$key] !== null && $dt[$key] !== '') {
                $query[$key] = $dt[$key];
            }
        }

        return $query;
    }
}

if (!function_exists('buildDtQuery')) {
    function applySearch($query, string $search, array $columns)
    {
        if (trim($search) === '')
            return $query;

        $driver = $query->getConnection()->getDriverName();
        $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($search, $columns, $like) {
            foreach ($columns as $i => $col) {
                if ($i === 0) {
                    $q->where($col, $like, "%{$search}%");
                } else {
                    $q->orWhere($col, $like, "%{$search}%");
                }
            }
        });
    }
}

if (!function_exists('applyDtOrdering')) {

    /**
     * Apply DataTables ordering safely.
     *
     * @param Builder|QueryBuilder $query
     * @param array $dtParams      Output of dtParams()
     * @param array $allowed       Allowed column names for ordering
     * @param string $defaultCol   Default column if none provided
     * @param string $defaultDir   Default direction (asc|desc)
     * @return Builder|QueryBuilder
     */
    function applyDtOrdering(
        $query,
        array $dtParams,
        array $allowed,
        string $defaultCol = 'id',
        string $defaultDir = 'desc'
    ) {

        $orders  = $dtParams['order'] ?? [];
        $columns = $dtParams['columns'] ?? [];

        if (!empty($orders) && is_array($orders)) {

            foreach ($orders as $order) {

                if (!isset($order['column'], $order['dir'])) {
                    continue;
                }

                $colIndex = (int) $order['column'];
                $dir = strtolower($order['dir']) === 'desc' ? 'desc' : 'asc';

                $colName = $columns[$colIndex]['data'] ?? null;

                if ($colName && in_array($colName, $allowed, true)) {
                    $query->orderBy($colName, $dir);
                }
            }

            return $query;
        }

        // Default ordering fallback
        return $query->orderBy($defaultCol, $defaultDir);
    }
}

if (!function_exists('dtResponse')) {

    /**
     * Build a DataTables response with search, filters, ordering and paging.
     *
     * @param Builder|QueryBuilder $baseQuery   Query without filters/search (used for recordsTotal)
     * @param array $dt                         Output of dtParams()
     * @param array $searchable                 Columns searchable by global search
     * @param array $sortable                   Allowed columns for ordering
     * @param callable|null $filters            function ($q, $dt) { ... } apply custom filters
     * @param array|null $select                Optional select columns
     * @param string $defaultSortCol
     * @param string $defaultSortDir
     * @return array
     */
    function dtResponse(
        $baseQuery,
        array $dt,
        array $searchable = [],
        array $sortable = [],
        ?callable $filters = null,
        ?array $select = null,
        string $defaultSortCol = 'id',
        string $defaultSortDir = 'desc'
    ): array {

        // Total count (no search/filters)
        $recordsTotal = (clone $baseQuery)->count();

        // Work query (apply filters/search/order/paging)
        $q = clone $baseQuery;

        // Custom filters (user_filter, created_from, created_to, etc.)
        if (is_callable($filters)) {
            $filters($q, $dt);
        }

        // Global search
        $search = trim((string)($dt['search'] ?? ''));
        if ($search !== '' && !empty($searchable)) {
            $driver = $q->getConnection()->getDriverName();
            $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

            $q->where(function ($qq) use ($search, $searchable, $like) {
                foreach (array_values($searchable) as $i => $col) {
                    if ($i === 0) {
                        $qq->where($col, $like, "%{$search}%");
                    } else {
                        $qq->orWhere($col, $like, "%{$search}%");
                    }
                }
            });
        }

        // Filtered count (after filters + search)
        $recordsFiltered = (clone $q)->count();

        // Ordering (safe)
        $orders  = $dt['order'] ?? [];
        $columns = $dt['columns'] ?? [];

        $appliedOrder = false;

        if (!empty($orders) && is_array($orders) && !empty($sortable)) {
            foreach ($orders as $order) {
                if (!isset($order['column'], $order['dir'])) continue;

                $colIndex = (int) $order['column'];
                $dir = strtolower($order['dir']) === 'desc' ? 'desc' : 'asc';
                $colName = $columns[$colIndex]['data'] ?? null;

                if ($colName && in_array($colName, $sortable, true)) {
                    $q->orderBy($colName, $dir);
                    $appliedOrder = true;
                }
            }
        }

        if (!$appliedOrder) {
            $q->orderBy($defaultSortCol, strtolower($defaultSortDir) === 'asc' ? 'asc' : 'desc');
        }

        // Paging
        $start  = (int)($dt['start'] ?? 0);
        $length = (int)($dt['length'] ?? 10);

        $q->skip(max(0, $start))->take(max(1, $length));

        // Select
        if (is_array($select) && !empty($select)) {
            $data = $q->get($select);
        } else {
            $data = $q->get();
        }

        return [
            'draw' => (int)($dt['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}