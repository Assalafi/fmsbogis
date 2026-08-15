<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_code_budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fiscal_year_id');
            $table->uuid('economic_code_id');
            $table->decimal('original_budget', 20, 2)->default(0);
            $table->decimal('supplementary_budget', 20, 2)->default(0);
            $table->decimal('virement_in', 20, 2)->default(0);
            $table->decimal('virement_out', 20, 2)->default(0);
            $table->decimal('revised_budget', 20, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'economic_code_id']);
            $table->index('status');

            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
            $table->foreign('economic_code_id')->references('id')->on('economic_codes')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_code_budgets');
    }
};
