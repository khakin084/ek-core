<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

if (!function_exists('auditLog')) {
    /**
     * Audit logger for create/update/delete with payload + changed fields.
     *
     * Best usage: pass the Model instance.
     *
     * @param  string       $module
     * @param  Model|null   $model        Eloquent model instance (preferred)
     * @param  int|null     $id           optional model id when $model not provided
     * @param  array|null   $payload      optional custom payload (defaults to request input)
     * @param  string|null  $event        override event: created|updated|deleted
     * @param  array        $hiddenKeys   keys to remove from payload (e.g. passwords)
     * @return void
     */
    function auditLog(
        string $module,
        ?Model $model = null,
        ?int $id = null,
        ?array $payload = null,
        ?string $event = null,
        array $hiddenKeys = ['password', 'password_confirmation', 'token', '_token']
    ): void {
        // --- Determine payload (sanitize) ---
        $rawPayload = $payload ?? Request::except($hiddenKeys);
        $safePayload = Arr::except($rawPayload, $hiddenKeys);

        // --- Determine event ---
        // If event is explicitly passed, use it; else infer.
        $resolvedEvent = $event;

        if (!$resolvedEvent) {
            if ($model instanceof Model) {
                // If model exists and has changes => updated; if it doesn't exist in DB yet => created
                $resolvedEvent = $model->exists ? 'updated' : 'created';
            } else {
                // fallback: if id exists => updated, else created
                $resolvedEvent = $id ? 'updated' : 'created';
            }
        }

        // --- Detect changes (only possible reliably with $model) ---
        $changes = null;
        if ($model instanceof Model) {
            // If called BEFORE save, dirty shows what's going to change.
            // If called AFTER save, use getChanges() for what changed.
            $dirty = $model->getDirty();     // before save
            $after = method_exists($model, 'getChanges') ? $model->getChanges() : [];

            // Prefer after-save changes when present, else use dirty
            $picked = !empty($after) ? $after : $dirty;

            if (!empty($picked)) {
                $original = $model->getOriginal();
                $changes = [];

                foreach ($picked as $key => $newValue) {
                    $changes[$key] = [
                        'from' => $original[$key] ?? null,
                        'to'   => $newValue,
                    ];
                }
            }
        }

        // --- Build description ---
        $modelLabel = $model ? class_basename($model) : null;
        $modelId = $model?->getKey() ?? $id;

        $verb = match ($resolvedEvent) {
            'created' => 'Recorded',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default   => ucfirst($resolvedEvent),
        };

        $desc = $verb;
        if ($modelLabel) {
            $desc .= " {$modelLabel}";
        }
        if ($modelId) {
            $desc .= " (#{$modelId})";
        }

        // --- Save log ---
        \App\Models\AccessLog::create([
            'user_id'    => Auth::id(),
            'module'     => $module,
            'description'=> $desc,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $modelId,
            'event'      => $resolvedEvent,
            'payload'    => $safePayload ?: null,
            'changes'    => $changes,
            'user_agent' => Request::userAgent(),
            'ip_address' => Request::ip(),
        ]);
    }
}