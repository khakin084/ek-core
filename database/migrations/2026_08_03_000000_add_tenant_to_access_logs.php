<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make the audit trail tenant-aware.
 *
 * Every audit row records WHICH tenant the action belonged to, so logs can be scoped per
 * tenant on read and one tenant can never see another's activity. Nullable, because a
 * platform-level action (no tenant scope) is legitimate and should still be audited.
 *
 * If access_logs is not yet deployed, add these lines to the create migration instead
 * (tenant_id right after id, plus the composite indexes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');

            // Every read filters by tenant, usually newest-first — index for it.
            $table->index(['tenant_id', 'created_at']);
            // Per-tenant model lookups.
            $table->index(['tenant_id', 'model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropIndex(['tenant_id', 'model_type', 'model_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
