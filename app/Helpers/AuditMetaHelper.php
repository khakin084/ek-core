<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if (!function_exists('auditVerb')) {
    function auditVerb(string $event): string
    {
        return match ($event) {
            'created' => 'Recorded',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default   => Str::headline($event),
        };
    }
}

if (!function_exists('auditDescription')) {
    function auditDescription(
        string $event,
        string $entity,
        ?string $displayName = null,
        ?array $changes = null
    ): string {
        $desc = auditVerb($event) . ' ' . $entity;

        // Raw, never headlined — this is user data, not a class name.
        if ($displayName !== null && $displayName !== '') {
            $desc .= " \"{$displayName}\"";
        }

        if ($event === 'updated' && $changes) {
            $desc .= ' - ' . implode(', ', array_keys($changes));
        }

        return $desc;
    }
}

if (!function_exists('auditMeta')) {
    function auditMeta(Model $model, array $options = []): array
    {
        // Event: explicit override > trait > Eloquent's own create flag.
        $event = $options['event']
            ?? $model->recentChangeEvent
            ?? ($model->wasRecentlyCreated ? 'created' : 'updated');

        // array_key_exists, not ??, so a caller can pass null to suppress the diff.
        $changes = array_key_exists('changes', $options)
            ? $options['changes']
            : $model->recentChanges;

        $nameField = $options['name_field']
            ?? (method_exists($model, 'auditNameField') ? $model->auditNameField() : null);

        $displayName = $nameField ? ($model->{$nameField} ?? null) : null;

        $entity = $options['entity']
            ?? request()->header('X-AUDIT-ENTITY')
            ?? Str::headline(class_basename($model));

        return [
            'event'       => $event,
            'module'      => $options['module'] ?? request()->header('X-AUDIT-MODULE', 'General'),
            'entity'      => $entity,
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'description' => auditDescription($event, $entity, $displayName, $changes),
            'changes'     => $changes,
        ];
    }
}