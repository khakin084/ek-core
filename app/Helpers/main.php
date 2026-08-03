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
     * Pulls DataTables values out of a request into a clean array.
     *
     * Reads the standard DataTables fields (draw, start, length, search)
     * and works whether the search comes in as search[value] (DataTables
     * default) or as a plain "search" string (our custom setup).
     *
     * You can also ask for extra filter fields via $extraKeys, and give
     * them fallback values via $defaults.
     *
     * @param Request $request
     * @param array $extraKeys  Extra request fields to grab, e.g. ['user_filter', 'created_from']
     * @param array $defaults   Fallback value per extra key if it's missing, e.g. ['user_filter' => 'all']
     * @param array $options    Optional tweaks:
     *   - min_length (int, default 1)         Smallest allowed page size
     *   - max_length (int|null, default null) Largest allowed page size (null = no cap)
     *   - include_order_columns (bool, true)  Also return 'order' and 'columns'
     *   - drop_empty (bool, true)             Skip extra keys that are null or ''
     *
     * @return array  ['draw', 'start', 'length', 'search', ...] plus any extra keys
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

        // Two callers, two shapes:
        //   - array  -> came straight from a DataTables AJAX request, where
        //               search lives under search[value].
        //   - string -> came from our own code/BFF that already flattened it
        //               into a plain "search" field.
        // Anything else (null, missing) is treated as no search.
        if (is_array($rawSearch)) {
            $search = $rawSearch['value'] ?? '';
        } elseif (is_string($rawSearch)) {
            $search = $rawSearch;
        } else {
            $search = '';
        }

        // Clamp page size into a sane range so a client can't request a
        // huge (or zero/negative) page. Floor at min_length, and cap at
        // max_length only if one was set.
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

        $orders = $dtParams['order'] ?? [];
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
     * Turns a query into a ready-to-return DataTables response.
     *
     * Handles the four things every DataTables endpoint needs: the global
     * search box, any custom filters you pass in, safe column ordering, and
     * paging. Also gives you the two counts DataTables expects
     * (recordsTotal = everything, recordsFiltered = after search/filters).
     *
     * @param Builder|QueryBuilder $baseQuery  Your query BEFORE search/filters.
     *                                         Used as-is for the total count,
     *                                         then cloned for the real work.
     * @param array $dt          The array you got back from dtParams().
     * @param array $searchable  Columns the global search box is allowed to hit.
     * @param array $sortable    Columns the user is allowed to sort by (whitelist).
     * @param callable|null $filters  Optional. function ($q, $dt) { ... } to apply
     *                                your own filters (user_filter, date range, etc).
     * @param array|null $select      Optional. Limit the returned columns.
     * @param string $defaultSortCol  Column to sort by when the user hasn't picked one.
     * @param string $defaultSortDir  'asc' or 'desc' for that default sort.
     *
     * @return array  ['draw', 'recordsTotal', 'recordsFiltered', 'data']
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

        // "recordsTotal" is the grand total with nothing filtered out, so we
        // count off a fresh clone before touching anything.
        $recordsTotal = (clone $baseQuery)->count();

        // Everything below builds on this working copy, leaving $baseQuery clean.
        $q = clone $baseQuery;

        // Your custom filters run first (user_filter, created_from/to, etc).
        if (is_callable($filters)) {
            $filters($q, $dt);
        }

        // Global search box.
        $search = trim((string) ($dt['search'] ?? ''));
        if ($search !== '' && !empty($searchable)) {
            // Postgres needs ILIKE for case-insensitive matching; MySQL's LIKE
            // is already case-insensitive, so pick based on the DB driver.
            $driver = $q->getConnection()->getDriverName();
            $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

            // Wrap the OR conditions in their own group so they don't leak out
            // and accidentally widen the filters applied above.
            // i.e. "... AND (colA LIKE x OR colB LIKE x OR ...)"
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

        // "recordsFiltered" is the count AFTER search/filters but BEFORE paging.
        $recordsFiltered = (clone $q)->count();

        // --- Ordering ---
        // DataTables sends the sorted column as a numeric index, not a name.
        // We map that index back to a column name, then only allow it if it's
        // in the $sortable whitelist. This is the important bit: never sort by
        // a raw client-supplied string, or you open the door to SQL injection.
        $orders = $dt['order'] ?? [];
        $columns = $dt['columns'] ?? [];

        $appliedOrder = false;

        if (!empty($orders) && is_array($orders) && !empty($sortable)) {
            foreach ($orders as $order) {
                if (!isset($order['column'], $order['dir']))
                    continue;

                $colIndex = (int) $order['column'];
                $dir = strtolower($order['dir']) === 'desc' ? 'desc' : 'asc';
                $colName = $columns[$colIndex]['data'] ?? null; // index -> name

                if ($colName && in_array($colName, $sortable, true)) {
                    $q->orderBy($colName, $dir);
                    $appliedOrder = true;
                }
            }
        }

        // Nothing valid to sort by? Fall back to the default so results are
        // at least in a stable order.
        if (!$appliedOrder) {
            $q->orderBy($defaultSortCol, strtolower($defaultSortDir) === 'asc' ? 'asc' : 'desc');
        }

        // --- Paging ---
        // Clamp so start can't go negative and length is always at least 1.
        $start = (int) ($dt['start'] ?? 0);
        $length = (int) ($dt['length'] ?? 10);

        $q->skip(max(0, $start))->take(max(1, $length));

        // Optionally trim down to just the columns the caller asked for.
        if (is_array($select) && !empty($select)) {
            $data = $q->get($select);
        } else {
            $data = $q->get();
        }

        return [
            'draw' => (int) ($dt['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}

/**
 * Redact common sensitive fields from payload logs.
 */
if (!function_exists('redact')) {
    function redact(array $payload): array
    {
        $sensitive = ['password', 'secret', 'token', 'access_token', 'refresh_token'];

        foreach ($sensitive as $k) {
            if (array_key_exists($k, $payload)) {
                $payload[$k] = '***';
            }
        }

        return $payload;
    }
}