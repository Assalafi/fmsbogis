<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_reconciliation_id');
            $table->uuid('cashbook_entry_id')->nullable();
            $table->uuid('bank_statement_line_id')->nullable();
            $table->string('item_type', 30);
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bank_reconciliation_id')->references('id')->on('bank_reconciliations')->cascadeOnDelete();
            $table->foreign('cashbook_entry_id')->references('id')->on('cashbook_entries')->nullOnDelete();
            $table->foreign('bank_statement_line_id')->references('id')->on('bank_statement_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
    }
};
