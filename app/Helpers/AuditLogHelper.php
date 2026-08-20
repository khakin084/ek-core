<?php

use App\Models\AccessLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

if (!function_exists('auditActor')) {
    /**
     * [tenant_id, user_id] for whatever context this is running in — BFF, service, or console.
     *
     * user_id is authUserId() (session UUID), NOT Auth::id(): on the BFF that's the guard's
     * INTEGER id, which misattributes the action and crashes on insert into the uuid column.
     */
    function auditActor(): array
    {
        $tenantId = function_exists('authTenantId')
            ? authTenantId()
            : (app()->bound(TenantContext::class) ? app(TenantContext::class)->id() : null);

        $userId = function_exists('authUserId')
            ? authUserId()
            : request()?->attributes->get('user_id');

        return [$tenantId, $userId];
    }
}

if (!function_exists('auditLog')) {
    /**
     * Write an audit row directly, for anything the response middleware can't see:
     * navigation, exports, console commands, queued jobs, multi-step operations.
     *
     * @param  string                   $module       X-AUDIT-MODULE equivalent, always explicit here.
     * @param  Model|string|null        $model        Model instance, class string, or plain entity label.
     * @param  int|string|null          $id           Record id when no model instance is available.
     * @param  array|null               $payload      null = capture request input; [] = store nothing.
     * @param  string|null              $event        created|updated|deleted|viewed|exported|...
     * @param  array                    $hiddenKeys   Stripped from payload.
     * @param  string|null              $description  Overrides the generated description outright.
     * @param  array|null               $changes      Overrides the trait's captured diff.
     * @param  bool                     $success      false prefixes "Failed:" and flags the row.
     */
    function auditLog(
        string $module,
        Model|string|null $model = null,
        int|string|null $id = null,
        ?array $payload = null,
        ?string $event = null,
        array $hiddenKeys = ['password', 'password_confirmation', 'token', '_token'],
        ?string $description = null,
        ?array $changes = null,
        bool $success = true
    ): void {
        // request() exists in console but is a synthetic stub — don't mine it for input or IP.
        $request = app()->runningInConsole() ? null : request();

        $payload = Arr::except($payload ?? $request?->except($hiddenKeys) ?? [], $hiddenKeys);

        $tracked = $model instanceof Model && property_exists($model, 'recentChangeEvent');

        // Event: explicit > trait > existence heuristic.
        $event ??= ($tracked ? $model->recentChangeEvent : null)
            ?? ($model instanceof Model
                ? ($model->exists ? 'updated' : 'created')
                : ($id !== null ? 'updated' : 'created'));

        // Diff comes from the trait only. Never reconstructed here — see note below.
        if ($changes === null && $tracked) {
            $changes = $model->recentChanges;
        }

        if ($model instanceof Model) {
            $meta = auditMeta($model, [
                'event' => $event,
                'changes' => $changes,
                'module' => $module,
            ]);
        } else {
            // String may be a class name or a bare label ("Farmer Register", "Dashboard").
            $entity = is_string($model) && $model !== ''
                ? (class_exists($model) ? Str::headline(class_basename($model)) : $model)
                : null;

            $meta = [
                'event' => $event,
                'module' => $module,
                'entity' => $entity,
                'model_type' => is_string($model) && class_exists($model) ? $model : null,
                'model_id' => $id,
                'description' => trim(auditVerb($event) . ' ' . ($entity ?? '')),
                'changes' => $changes,
            ];
        }

        if ($description !== null) {
            $meta['description'] = $description;
        }

        if (!$success) {
            $meta['description'] = 'Failed: ' . $meta['description'];
        }

        [$tenantId, $userId] = auditActor();

        // Best-effort: a logging failure must never break the caller's operation.
        try {
            AccessLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'module' => $meta['module'],
                'description' => $meta['description'],
                'model_type' => $meta['model_type'],
                'model_id' => $meta['model_id'],
                'event' => $meta['event'],
                'payload' => $payload ?: null,
                'changes' => $meta['changes'],
                'success' => $success,
                'user_agent' => $request?->userAgent(),
                'ip_address' => $request?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('auditLog write failed (caller unaffected)', [
                'error' => $e->getMessage(),
                'module' => $module,
                'event' => $meta['event'],
            ]);
        }
    }
}