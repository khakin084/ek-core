<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Makes a model tenant-owned. Two behaviours, both automatic:
 *
 *   1. STAMP ON CREATE — a new record inherits the acting tenant. You never set tenant_id in
 *      a controller; forgetting is impossible.
 *
 *   2. SCOPE ON READ — every query is filtered to the acting tenant. select / update / delete
 *      can only ever touch the current tenant's rows, so a guessed id from another tenant
 *      resolves to nothing.
 *
 * USE:
 *   class Sale extends Model { use BelongsToTenant; }
 *   Every tenant-owned table needs a `tenant_id` column (uuid) + index.
 *
 * DO NOT put tenant_id in $fillable. It is stamped server-side from the token; making it
 * mass-assignable would let a request body override the tenant — the exact leak this
 * prevents.
 *
 * FAIL-CLOSED IN REQUESTS: if a web/api request reaches a write with no tenant in scope,
 * that is a bug (auth middleware guarantees one), so we throw rather than write an
 * unscoped, orphaned, or cross-tenant row. Outside requests (console/jobs) an unset tenant
 * is allowed — those contexts opt in explicitly via TenantContext::set().
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        $tenant = fn (): TenantContext => app(TenantContext::class);

        // 1. Stamp on create.
        static::creating(function (Model $model) use ($tenant) {
            if (! empty($model->tenant_id)) {
                return; // explicit tenant (e.g. platform admin cross-tenant create) is honoured
            }

            $tenantId = $tenant()->id();

            if ($tenantId === null) {
                // In a request this must never happen. In console/jobs, set the tenant first.
                if (app()->runningInConsole() === false) {
                    throw new RuntimeException(
                        sprintf('Refusing to create %s with no tenant in scope.', static::class)
                    );
                }

                return;
            }

            $model->tenant_id = $tenantId;
        });

        // 2. Scope every query to the acting tenant.
        static::addGlobalScope('tenant', function (Builder $builder) use ($tenant) {
            $tenantId = $tenant()->id();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
            // No tenant in scope -> scope adds nothing. Fine for console/seeders operating
            // across tenants deliberately; a request always has one, so this branch is
            // console-only in practice.
        });
    }

    /** Escape hatch for legitimate cross-tenant reads (platform admin, reporting). */
    public static function acrossAllTenants(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
