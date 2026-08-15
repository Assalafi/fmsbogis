<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->date('statement_from');
            $table->date('statement_to');
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('closing_balance', 20, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('account_id');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
