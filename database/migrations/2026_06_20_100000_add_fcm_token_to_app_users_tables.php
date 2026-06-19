<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'vendors', 'drivers'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('fcm_token', 500)->nullable()->after('mobile');
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'vendors', 'drivers'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }
    }
};
