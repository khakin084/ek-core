<?php

if (!function_exists('apiSuccess')) {
    function apiSuccess(
        string $msg,
        array $data = [],
        ?array $audit = null
    ) {
        $response = [
            'state' => 'Done',
            'title' => 'Successful',
            'msg' => $msg,
        ];

        $response = array_merge($response, $data);

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
        int|string|null $id = null
    ) {
        $response = [
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => $msg,
        ];

        $response = array_merge($response, $data);
        $response['meta']['audit'] = [
            'event' => $id ? 'updated' : 'created',
            'module' => request()->header('X-AUDIT-MODULE', 'General'),
            'entity' => request()->header('X-AUDIT-ENTITY', 'Model'),
            'model_id' => $id,
            'description' => 'Failed operation',
        ];

        return response()->json($response, $status);
    }
}