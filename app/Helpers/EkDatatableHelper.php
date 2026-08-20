<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/*
|------------------------------------------------------------------------------
| DataTables helpers — the shared contract between BFF and downstream services
|------------------------------------------------------------------------------
|
| THE CHAIN
|   BFF controller    dtParams($request, $filterKeys)   parse the browser's request
|   BFF service       dtForward($dt, $filterKeys)       build the outbound query
|   downstream ctrl   dtParams($request, $filterKeys)   parse the forwarded query
|   downstream ctrl   dtResponse($base, $dt, ...)       run it, return DataTables shape
|
| DECLARE THE FILTER KEYS ONCE. Put them on the service as a constant and pass the same
| array to both dtParams() and dtForward(). Declaring them in only one of the two is why
| filters silently vanish:
|
|   class ApprovalService {
|       public const FILTERS = ['requester','request_type','request_status','date_from','date_to'];
|   }
|
|   // BFF controller
|   $dt = dtParams($request, ApprovalService::FILTERS);
|   // BFF service
|   $query = dtForward($dt, ApprovalService::FILTERS);
|   // downstream controller
|   $dt = dtParams($request, ApprovalService::FILTERS);
|
| SORTING IS FLATTENED AT THE EDGE. DataTables sends order[0][column]=2 plus a verbose
| columns[] array (~6 params per column — 8 columns is ~50 query params, and it is the main
| thing pushing these URLs toward length limits). dtParams() resolves that to a flat
| sort_by / sort_dir pair immediately, so only two params cross the wire and downstream never
| has to understand DataTables' index-to-name mapping.
|
| Single-column sort only. Multi-column (shift-click) collapses to the first — add a compact
| encoding here if you ever need it.
*/

if (!function_exists('dtParams')) {
    /**
     * Pull DataTables values out of a request into a clean array.
     *
     * Accepts BOTH shapes:
     *   - a browser DataTables request  (search[value], order[], columns[])
     *   - an already-flattened forward  (search, sort_by, sort_dir)   <- what dtForward sends
     *
     * @param array $extraKeys Filter fields to grab, e.g. ['requester','date_from']
     * @param array $defaults  Fallback per extra key, e.g. ['requester' => 'all']
     * @param array $options   min_length (1), max_length (null), drop_empty (true)
     */
    function dtParams(
        Request $request,
        array $extraKeys = [],
        array $defaults = [],
        array $options = []
    ): array {

        $minLength = (int) ($options['min_length'] ?? 1);
        $maxLength = $options['max_length'] ?? null;
        $dropEmpty = (bool) ($options['drop_empty'] ?? true);

        // search arrives as search[value] from DataTables, or a plain string from dtForward.
        $rawSearch = $request->input('search');
        $search = is_array($rawSearch) ? ($rawSearch['value'] ?? '')
                : (is_string($rawSearch) ? $rawSearch : '');

        $length = (int) $request->input('length', 10);
        $length = max($minLength, $length);
        if ($maxLength !== null) {
            $length = min((int) $maxLength, $length);
        }

        // --- resolve ordering to a flat pair, whichever shape came in ---
        $sortBy  = $request->input('sort_by');
        $sortDir = strtolower((string) $request->input('sort_dir', '')) === 'asc' ? 'asc' : 'desc';

        if (!$sortBy) {
            $orders  = $request->input('order', []);
            $columns = $request->input('columns', []);

            if (is_array($orders) && isset($orders[0]['column'])) {
                $idx     = (int) $orders[0]['column'];
                $sortBy  = $columns[$idx]['data'] ?? null;     // index -> the column's data key
                $sortDir = strtolower($orders[0]['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
            }
        }

        $params = [
            'draw'     => (int) $request->input('draw', 1),
            'start'    => max(0, (int) $request->input('start', 0)),
            'length'   => $length,
            'search'   => trim($search),
            'sort_by'  => $sortBy ?: null,
            'sort_dir' => $sortDir,
        ];

        foreach ($extraKeys as $key) {
            $val = $request->input($key, $defaults[$key] ?? null);

            if (!$dropEmpty || ($val !== null && $val !== '')) {
                $params[$key] = $val;
            }
        }

        return $params;
    }
}

if (!function_exists('dtForward')) {
    /**
     * Build the outbound query for a downstream list endpoint.
     *
     * Forwards the flat sort pair, NOT order[]/columns[] — two params instead of ~50, and
     * downstream stays ignorant of DataTables' wire format.
     *
     * `draw` is deliberately NOT forwarded: it is a browser-side echo counter and the BFF
     * should return its own, not trust whatever downstream sends back.
     *
     * @param array $filterKeys the SAME array passed to dtParams()
     */
    function dtForward(array $dt, array $filterKeys = []): array
    {
        $query = [
            'start'  => (int) ($dt['start'] ?? 0),
            'length' => (int) ($dt['length'] ?? 10),
            'search' => (string) ($dt['search'] ?? ''),
        ];

        if (!empty($dt['sort_by'])) {
            $query['sort_by']  = $dt['sort_by'];
            $query['sort_dir'] = $dt['sort_dir'] ?? 'desc';
        }

        foreach ($filterKeys as $key) {
            if (array_key_exists($key, $dt) && $dt[$key] !== null && $dt[$key] !== '') {
                $query[$key] = $dt[$key];
            }
        }

        return $query;
    }
}

// Back-compat shim. Old call sites pass the filter list as the 2nd arg, same as dtForward.
if (!function_exists('buildDtQuery')) {
    function buildDtQuery(array $dt, array $extraFilters = []): array
    {
        return dtForward($dt, $extraFilters);
    }
}

if (!function_exists('dtResponse')) {
    /**
     * Run a query and return the DataTables response shape.
     *
     * NAMED OPTIONS (use this form):
     *
     *     return response()->json(dtResponse($base, $dt, [
     *         'searchable' => [DB::raw($costCenter), 'ar.status'],
     *         'sortable'   => ['request_date' => 'ar.created_at', 'value' => 'ar.amount'],
     *         'filters'    => function ($q, $dt) { ... },
     *         'sort'       => ['ar.created_at', 'desc'],   // default when the user hasn't sorted
     *         'tiebreaker' => 'ar.id',
     *     ]));
     *
     * Why an options array rather than positional arguments: the old signature had eight
     * parameters, five optional. Dropping or shifting one produced either a TypeError or —
     * worse — a silently wrong sort when the types happened to line up. Named keys make order
     * irrelevant, let new options be added without touching a single call site, and surface a
     * typo'd key immediately (see the unknown-key guard below) instead of at 2am.
     *
     * LEGACY POSITIONAL FORM still works: if the third argument is a LIST (not an associative
     * array) it is read as the old
     *   ($searchable, $sortable, $filters, $defaultSortCol, $defaultSortDir, $tiebreaker)
     * ordering, so existing services keep running while they migrate.
     *
     * OPTIONS
     *   searchable  array   Columns/expressions the search box may hit. Strings or DB::raw().
     *                       Suffix a numeric/date column with '::text' to force a CAST — a bare
     *                       numeric with ILIKE errors on Postgres.
     *   sortable    array   ['datatable_alias' => 'sql_column_or_expression'].
     *                       Keys MUST be the DataTable column `data` names — that is what
     *                       arrives as sort_by. A plain list of DB columns never matches and
     *                       sorting dies silently.
     *   filters     callable  function ($query, $dt) {} for custom filters.
     *   sort        array   [column, direction] used when the user hasn't chosen a sort.
     *   tiebreaker  string  Unique column appended to ORDER BY. Without it, ties on a
     *                       non-unique sort column have undefined order and rows repeat or
     *                       vanish between pages.
     */
    function dtResponse($baseQuery, array $dt, array $options = [], ...$legacy): array
    {
        // ---- accept the legacy positional call ----
        if ($options !== [] && array_is_list($options)) {
            $options = [
                'searchable' => $options,
                'sortable'   => $legacy[0] ?? [],
                'filters'    => $legacy[1] ?? null,
                'sort'       => [$legacy[2] ?? 'id', $legacy[3] ?? 'desc'],
                'tiebreaker' => $legacy[4] ?? null,
            ];
        }
 
        // ---- fail loudly on a mistyped key instead of ignoring it ----
        $known = ['searchable', 'sortable', 'filters', 'sort', 'tiebreaker'];
        if ($unknown = array_diff(array_keys($options), $known)) {
            throw new InvalidArgumentException(
                'dtResponse(): unknown option(s) [' . implode(', ', $unknown) . ']. Valid: '
                . implode(', ', $known) . '.'
            );
        }
 
        $searchable = $options['searchable'] ?? [];
        $sortable   = $options['sortable']   ?? [];
        $filters    = $options['filters']    ?? null;
        $tiebreaker = $options['tiebreaker'] ?? null;
        [$defaultSortCol, $defaultSortDir] = $options['sort'] ?? ['id', 'desc'];
 
        $recordsTotal = (clone $baseQuery)->count();
        $q = clone $baseQuery;
 
        if (is_callable($filters)) {
            $filters($q, $dt);
        }
 
        $driver = $q->getConnection()->getDriverName();
        $like   = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
 
        $narrowed = is_callable($filters);
        $search   = trim((string) ($dt['search'] ?? ''));
 
        if ($search !== '' && !empty($searchable)) {
            $narrowed = true;
 
            $q->where(function ($qq) use ($search, $searchable, $like, $driver) {
                foreach (array_values($searchable) as $i => $col) {
                    $target = $col;
 
                    if (!$col instanceof Expression && str_ends_with((string) $col, '::text')) {
                        $plain  = substr($col, 0, -6);
                        $target = new Expression(
                            $driver === 'pgsql' ? "CAST({$plain} AS TEXT)" : "CAST({$plain} AS CHAR)"
                        );
                    }
 
                    $i === 0
                        ? $qq->where($target, $like, "%{$search}%")
                        : $qq->orWhere($target, $like, "%{$search}%");
                }
            });
        }
 
        $recordsFiltered = $narrowed ? (clone $q)->count() : $recordsTotal;
 
        $sortBy  = $dt['sort_by'] ?? null;
        $sortDir = ($dt['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
 
        if ($sortBy && array_key_exists($sortBy, $sortable)) {
            $q->orderBy($sortable[$sortBy], $sortDir);
        } else {
            $q->orderBy($defaultSortCol, strtolower($defaultSortDir) === 'asc' ? 'asc' : 'desc');
        }
 
        if ($tiebreaker) {
            $q->orderBy($tiebreaker, 'desc');
        }
 
        $q->skip(max(0, (int) ($dt['start'] ?? 0)))
          ->take(max(1, (int) ($dt['length'] ?? 10)));
 
        return [
            'draw'            => (int) ($dt['draw'] ?? 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $q->get(),
        ];
    }
}

if (!function_exists('dtRelay')) {
    /**
     * Relay a downstream list result back to the browser in DataTables shape.
     *
     * Every BFF dataTable() method ends with this ONE line:
     *
     *     return dtRelay($dt, $this->approvalService->getApprovalsDataTable($dt));
     *
     * It exists so nobody hand-rolls the response block again. The hand-rolled version had
     * four failure modes, all of which are silent in the browser:
     *
     *  1. WRONG STATUS KILLS THE MESSAGE. DataTables only parses the body on a 2xx. Returning
     *     502 with an 'error' key means the error is NEVER shown — the grid just sits blank.
     *     We always return 200 and put the failure in 'error', which DataTables surfaces.
     *     The real detail goes to the log, not the browser.
     *
     *  2. DRAW MISMATCH DISCARDS THE RESPONSE. DataTables ignores any response whose draw is
     *     older than the request it is waiting on. Echoing downstream's draw (usually a
     *     constant 1) makes every response after the first silently vanish — the table looks
     *     frozen with no console error. draw is ALWAYS the caller's.
     *
     *  3. `?? 0` ON ONE COUNT ONLY. recordsTotal missing while recordsFiltered survives gives
     *     total=0 filtered=N, and DataTables renders nonsense paging ("Showing 1 to 10 of 0").
     *     Both counts are normalised together and filtered is clamped to total.
     *
     *  4. NON-LIST data BECOMES A JSON OBJECT. A PHP array with non-sequential keys encodes as
     *     {"0":...,"2":...} instead of [...], and DataTables throws "Invalid JSON response".
     *     array_values() guarantees a real array.
     *
     * The failure message is deliberately generic and carries a reference id. Naming the
     * resource in a hand-written string is what produced "Failed to load item groups" inside
     * an approvals controller — a message that actively misleads whoever debugs it. The log
     * line below carries the real context, derived automatically from the calling method.
     *
     * @param array $dt      From dtParams() — supplies the authoritative draw.
     * @param mixed $result  Whatever the service client returned.
     * @param string|null $context  Optional label for the log. Leave null: it is derived from
     *                              the caller, so it can never be a stale copy-paste.
     */
    function dtRelay(array $dt, $result, ?string $context = null): JsonResponse
    {
        $draw = (int) ($dt['draw'] ?? 1);
 
        // Derive "ApprovalController::dataTable" from the call site so the log is always right
        // even when the method was copy-pasted from another module.
        if ($context === null) {
            $frame   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
            $context = ($frame['class'] ?? 'unknown') . '::' . ($frame['function'] ?? 'unknown');
        }
 
        // A usable result is an array carrying a 'data' key. null, [], false, an error payload,
        // or a scalar are all failures — `?? ` alone only catches null and would let [] through.
        $usable = is_array($result) && array_key_exists('data', $result);
 
        if (!$usable) {
            $reference = (string) (request()?->header('X-Correlation-ID') ?: Str::uuid());
 
            // The browser gets a reference; the log gets everything needed to trace it.
            Log::error('DataTables relay: downstream returned no usable result', [
                'context'   => $context,
                'reference' => $reference,
                'path'      => request()?->path(),
                'received'  => is_array($result)
                    ? ['keys' => array_keys($result)]
                    : ['type' => get_debug_type($result)],
            ]);
 
            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => "Could not load this list. Reference: {$reference}",
            ]);   // 200 on purpose — see note 1
        }
 
        // Normalise the payload. Downstream is another service; treat its numbers as untrusted.
        $data = $result['data'];
 
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->all();
        }
 
        $data = is_array($data) ? array_values($data) : [];   // guarantee a JSON array
 
        $total    = max(0, (int) ($result['recordsTotal'] ?? 0));
        $filtered = max(0, (int) ($result['recordsFiltered'] ?? $total));
 
        // filtered can never exceed total; if downstream disagrees, trust the larger as total so
        // paging stays coherent instead of DataTables computing negative pages.
        if ($filtered > $total) {
            $total = $filtered;
        }
 
        // A page of rows with both counts at zero means downstream forgot the counts. Rather than
        // render an empty-looking grid that clearly has rows, fall back to the row count.
        if ($total === 0 && $data !== []) {
            $total = $filtered = count($data);
        }
 
        return response()->json([
            'draw'            => $draw,        // ALWAYS ours — see note 2
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }
}
 
if (!function_exists('dtEmpty')) {
    /**
     * A valid empty DataTables response. For guard clauses that must return before any
     * downstream call — e.g. no tenant in scope, or a permission short-circuit.
     *
     *     if (blank($tenantId)) return dtEmpty($dt, 'No tenant context.');
     */
    function dtEmpty(array $dt, ?string $error = null): JsonResponse
    {
        return response()->json(array_filter([
            'draw'            => (int) ($dt['draw'] ?? 1),
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'data'            => [],
            'error'           => $error,
        ], fn ($v) => $v !== null));
    }
}
