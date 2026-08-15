<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fiscal_year_id');
            $table->uuid('from_economic_code_id');
            $table->uuid('to_economic_code_id');
            $table->decimal('amount', 20, 2);
            $table->date('date');
            $table->string('reference_number');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
            $table->foreign('from_economic_code_id')->references('id')->on('economic_codes')->cascadeOnDelete();
            $table->foreign('to_economic_code_id')->references('id')->on('economic_codes')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virements');
    }
};
