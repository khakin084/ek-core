<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit record. Writes are stamped explicitly (middleware / auditLog helper), so this model
 * does NOT auto-stamp on create — but it DOES scope READS to the acting tenant, so an audit
 * list view only ever shows the current tenant's activity.
 *
 * Kept separate from BelongsToTenant deliberately: that trait fail-closes (throws) when a
 * write has no tenant in scope, and audit must never throw — losing an audit row is worse
 * than a null tenant, and it must not break the request being audited.
 */
class AccessLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'module',
        'description',
        'model_type',
        'model_id',
        'event',
        'success',
        'payload',
        'changes',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'changes' => 'array',
    ];

    protected static function booted(): void
    {
        // Scope reads to the current tenant. Null-safe: no tenant in scope (console, platform
        // context) means no filter — deliberate, so platform tooling can read across tenants.
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    /** Platform reporting across every tenant. */
    public static function acrossAllTenants(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}