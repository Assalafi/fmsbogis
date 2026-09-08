<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            $table->unique(
                ['bank_reconciliation_id', 'cashbook_entry_id'],
                'reconciliation_cashbook_entry_unique'
            );
            $table->unique(
                ['bank_reconciliation_id', 'bank_statement_line_id'],
                'reconciliation_statement_line_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            $table->dropUnique('reconciliation_cashbook_entry_unique');
            $table->dropUnique('reconciliation_statement_line_unique');
        });
    }
};
