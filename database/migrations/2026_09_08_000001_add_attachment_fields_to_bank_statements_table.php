<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('closing_balance');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_mime')->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
            $table->text('notes')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_mime', 'file_size', 'notes']);
        });
    }
};
