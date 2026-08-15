<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('treasury_receipt_voucher_number');
            $table->string('station')->nullable()->after('receipt_number');
            $table->string('head_no')->nullable()->after('station');
            $table->string('sub_head_no')->nullable()->after('head_no');
            $table->string('payer_phone')->nullable()->after('from_whom_received_to_whom_paid');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'station', 'head_no', 'sub_head_no', 'payer_phone']);
        });
    }
};
