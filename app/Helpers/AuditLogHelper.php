<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

if (!function_exists('auditLog')) {
    /**
     * Audit logger for create/update/delete with payload + changed fields.
     *
     * Multi-tenant changes vs the original:
     *   - stamps tenant_id from the acting tenant (authTenantId / TenantContext),
     *   - actor is authUserId() (session UUID), NOT Auth::id() — on the BFF Auth::id() is the
     *     guard's INTEGER id, which both misattributes the action and crashes on insert into
     *     the uuid user_id column,
     *   - the write is wrapped so a logging failure never breaks the caller's operation.
     */
    function auditLog(
        string $module,
        Model|string|null $model = null,
        int|string|null $id = null,
        ?array $payload = null,
        ?string $event = null,
        array $hiddenKeys = ['password', 'password_confirmation', 'token', '_token']
    ): void {
        $rawPayload = $payload ?? Request::except($hiddenKeys);
        $safePayload = Arr::except($rawPayload, $hiddenKeys);

        $resolvedEvent = $event;
        if (!$resolvedEvent) {
            $resolvedEvent = $model instanceof Model
                ? ($model->exists ? 'updated' : 'created')
                : ($id ? 'updated' : 'created');
        }

        $changes = null;
        if ($model instanceof Model) {
            $after = method_exists($model, 'getChanges') ? $model->getChanges() : [];
            $dirty = $model->getDirty();
            $picked = !empty($after) ? $after : $dirty;

            if (!empty($picked)) {
                $original = $model->getOriginal();
                $changes = [];
                foreach ($picked as $key => $newValue) {
                    $changes[$key] = ['from' => $original[$key] ?? null, 'to' => $newValue];
                }
            }
        }

        $modelLabel = ($model instanceof Model) ? class_basename($model) : null;
        $modelId = ($model instanceof Model) ? $model->getKey() : $id;
        $modelType = ($model instanceof Model) ? get_class($model) : null;

        $verb = match ($resolvedEvent) {
            'created' => 'Recorded',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default => ucfirst($resolvedEvent),
        };

        $desc = $verb;
        if ($modelLabel)
            $desc .= " {$modelLabel}";
        if ($modelId)
            $desc .= " (#{$modelId})";

        // Resolve tenant + actor from whatever this context provides, defensively — the helper
        // may be called from the BFF (authTenantId/authUserId) or a service (TenantContext).
        $tenantId = function_exists('authTenantId')
            ? authTenantId()
            : (app()->bound(\App\Support\TenantContext::class)
                ? app(\App\Support\TenantContext::class)->id()
                : null);

        $userId = function_exists('authUserId')
            ? authUserId()
            : (request()?->attributes->get('user_id'));

        try {
            \App\Models\AccessLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'module' => $module,
                'description' => $desc,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'event' => $resolvedEvent,
                'payload' => $safePayload ?: null,
                'changes' => $changes,
                'user_agent' => Request::userAgent(),
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('auditLog write failed (caller unaffected)', [
                'error' => $e->getMessage(),
                'module' => $module,
            ]);
        }
    }
}