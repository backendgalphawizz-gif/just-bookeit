<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('vehicle_no');
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('ifsc_code', 11)->nullable()->after('account_number');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->enum('account_type', ['savings', 'current'])->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'account_name',
                'account_number',
                'ifsc_code',
                'bank_name',
                'account_type',
            ]);
        });
    }
};
