<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_clearances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fiscal_year_id');
            $table->uuid('economic_code_id');
            $table->string('mda_code', 30);
            $table->string('mda_name');
            $table->decimal('approved_budget_snapshot', 20, 2);
            $table->decimal('fund_available_before', 20, 2);
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->text('payee_name')->nullable();
            $table->text('purpose');
            $table->text('activity')->nullable();
            $table->text('remark')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approval_type', 60)->nullable();
            $table->string('status', 20)->default('approved');
            $table->timestamp('approved_at')->nullable();
            $table->string('source_system', 30);
            $table->string('source_id', 64);
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            // Nullable keeps this migration compatible with older MySQL/MariaDB
            // timestamp rules; synchronised records always populate the value.
            $table->timestamp('source_synced_at')->nullable();
            $table->boolean('source_active')->default(true);
            $table->timestamps();

            $table->unique(['source_system', 'source_id']);
            $table->index(['fiscal_year_id', 'source_active', 'status'], 'clearances_fy_active_status_index');
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
            $table->foreign('economic_code_id')->references('id')->on('economic_codes')->cascadeOnDelete();
        });

        Schema::table('ebudget_sync_runs', function (Blueprint $table) {
            $table->unsignedInteger('clearances_received')->default(0)->after('virements_synced');
            $table->unsignedInteger('clearances_synced')->default(0)->after('clearances_received');
        });
    }

    public function down(): void
    {
        Schema::table('ebudget_sync_runs', function (Blueprint $table) {
            $table->dropColumn(['clearances_received', 'clearances_synced']);
        });

        Schema::dropIfExists('budget_clearances');
    }
};
