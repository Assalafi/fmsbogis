<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('bank_statement_id');
            $table->date('reconciliation_date');
            $table->decimal('cashbook_balance', 20, 2)->default(0);
            $table->decimal('bank_statement_balance', 20, 2)->default(0);
            $table->decimal('adjusted_cashbook_balance', 20, 2)->default(0);
            $table->decimal('adjusted_bank_balance', 20, 2)->default(0);
            $table->decimal('difference', 20, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->uuid('prepared_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('reconciliation_date');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('bank_statement_id')->references('id')->on('bank_statements')->cascadeOnDelete();
            $table->foreign('prepared_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
