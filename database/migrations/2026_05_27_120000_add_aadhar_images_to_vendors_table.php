<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('aadhar_front_path')->nullable()->after('city');
            $table->string('aadhar_back_path')->nullable()->after('aadhar_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['aadhar_front_path', 'aadhar_back_path']);
        });
    }
};
