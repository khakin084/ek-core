<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if (!function_exists('apiSuccess')) {
    function apiSuccess(
        string $msg,
        array $data = [],
        Model|array|null $audit = null
    ) {
        $response = [
            'state' => 'Done',
            'title' => 'Successful',
            'msg' => $msg,
        ];

        $response = array_merge($response, $data);

        if ($audit instanceof Model) {
            $audit = auditMeta($audit);
        }

        if ($audit) {
            $response['meta']['audit'] = $audit;
        }

        return response()->json($response);
    }
}

if (!function_exists('apiFail')) {
    function apiFail(
        string $msg,
        int $status = 500,
        array $data = [],
        Model|array|int|string|null $audit = null,
        ?string $event = null
    ) {
        $response = [
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => $msg,
        ];

        $response = array_merge($response, $data);

        $meta = null;

        if ($audit instanceof Model) {
            // Full metadata, then override the description so a failed write is never
            // mistaken for a successful one in access_log.
            $meta = auditMeta($audit, $event ? ['event' => $event] : []);
            $meta['description'] = 'Failed: ' . $meta['description'];
        } elseif (is_array($audit)) {
            $meta = $audit;
            $meta['description'] = 'Failed: ' . ($meta['description'] ?? 'operation');
        } elseif ($audit !== null) {
            // Bare id — no model to introspect, so build the minimum.
            $entity = request()->header('X-AUDIT-ENTITY', 'Model');
            $meta = [
                'event' => $event ?? 'updated',
                'module' => request()->header('X-AUDIT-MODULE', 'General'),
                'entity' => $entity,
                'model_type' => null,
                'model_id' => $audit,
                'description' => 'Failed: ' . auditVerb($event ?? 'updated') . ' ' . $entity,
                'changes' => null,
            ];
        }

        if ($meta) {
            $meta['failed'] = true;
            $meta['status'] = $status;
            $response['meta']['audit'] = $meta;
        }

        return response()->json($response, $status);
    }
}