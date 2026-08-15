<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('external_reference')->nullable()->unique()->after('treasury_receipt_voucher_number');
            $table->string('external_source')->nullable()->after('external_reference');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique(['external_reference']);
            $table->dropColumn(['external_reference', 'external_source']);
        });
    }
};
