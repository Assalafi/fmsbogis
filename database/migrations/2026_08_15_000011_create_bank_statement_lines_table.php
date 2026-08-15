<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_statement_id');
            $table->date('date_of_transaction');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->decimal('balance', 20, 2)->nullable();
            $table->string('match_status', 20)->default('unmatched');
            $table->timestamps();

            $table->index('bank_statement_id');
            $table->index('match_status');

            $table->foreign('bank_statement_id')->references('id')->on('bank_statements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
