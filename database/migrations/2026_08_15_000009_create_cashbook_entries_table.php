<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('economic_code_id')->nullable();
            $table->uuid('fiscal_year_id')->nullable();
            $table->string('transaction_type', 30);
            $table->uuid('transaction_id')->nullable();
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('details')->nullable();
            $table->decimal('receipt_amount', 20, 2)->default(0);
            $table->decimal('payment_amount', 20, 2)->default(0);
            $table->decimal('running_balance', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['transaction_type', 'transaction_id']);
            $table->index('account_id');
            $table->index('date');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('economic_code_id')->references('id')->on('economic_codes')->nullOnDelete();
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
