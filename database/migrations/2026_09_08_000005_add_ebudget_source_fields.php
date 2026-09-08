<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('economic_code_budgets', function (Blueprint $table) {
            $table->string('source_system', 30)->nullable()->after('approved_at');
            $table->string('source_id', 64)->nullable()->after('source_system');
            $table->decimal('source_available_funds', 20, 2)->nullable()->after('source_id');
            $table->timestamp('source_updated_at')->nullable()->after('source_available_funds');
            $table->timestamp('source_synced_at')->nullable()->after('source_updated_at');
            $table->boolean('source_active')->default(true)->after('source_synced_at');
            $table->unique(['source_system', 'source_id']);
            $table->index(['fiscal_year_id', 'source_active']);
        });

        Schema::table('virements', function (Blueprint $table) {
            $table->string('from_mda_code', 30)->nullable()->after('fiscal_year_id');
            $table->string('from_mda_name')->nullable()->after('from_mda_code');
            $table->string('to_mda_code', 30)->nullable()->after('from_economic_code_id');
            $table->string('to_mda_name')->nullable()->after('to_mda_code');
            $table->string('approval_type', 60)->nullable()->after('reason');
            $table->string('source_system', 30)->nullable()->after('approved_at');
            $table->string('source_id', 64)->nullable()->after('source_system');
            $table->timestamp('source_updated_at')->nullable()->after('source_id');
            $table->timestamp('source_synced_at')->nullable()->after('source_updated_at');
            $table->boolean('source_active')->default(true)->after('source_synced_at');
            $table->unique(['source_system', 'source_id']);
            $table->index(['fiscal_year_id', 'source_active']);
        });

        Schema::create('ebudget_sync_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fiscal_year_id')->nullable();
            $table->string('source_session', 10);
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('budgets_received')->default(0);
            $table->unsignedInteger('budgets_synced')->default(0);
            $table->unsignedInteger('virements_received')->default(0);
            $table->unsignedInteger('virements_synced')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->text('message')->nullable();
            $table->uuid('requested_by')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['source_session', 'status']);
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebudget_sync_runs');

        Schema::table('virements', function (Blueprint $table) {
            $table->dropUnique(['source_system', 'source_id']);
            $table->dropIndex(['fiscal_year_id', 'source_active']);
            $table->dropColumn([
                'from_mda_code', 'from_mda_name', 'to_mda_code', 'to_mda_name',
                'approval_type', 'source_system', 'source_id', 'source_updated_at',
                'source_synced_at', 'source_active',
            ]);
        });

        Schema::table('economic_code_budgets', function (Blueprint $table) {
            $table->dropUnique(['source_system', 'source_id']);
            $table->dropIndex(['fiscal_year_id', 'source_active']);
            $table->dropColumn([
                'source_system', 'source_id', 'source_available_funds',
                'source_updated_at', 'source_synced_at', 'source_active',
            ]);
        });
    }
};
