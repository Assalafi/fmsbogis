<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('economic_code_id');
            $table->uuid('fiscal_year_id');
            $table->date('date_of_transaction');
            $table->decimal('amount', 20, 2);
            $table->string('treasury_receipt_voucher_number');
            $table->string('from_whom_received_to_whom_paid')->nullable();
            $table->string('bank_credit_slip_cheque_mandate_number')->nullable();
            $table->string('dept_voucher_number')->nullable();
            $table->string('payment_method', 20);
            $table->text('details');
            $table->string('status', 20)->default('pending');
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->uuid('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('economic_code_id');
            $table->index('fiscal_year_id');
            $table->index('date_of_transaction');
            $table->index('status');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('economic_code_id')->references('id')->on('economic_codes')->cascadeOnDelete();
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
