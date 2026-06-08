<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('name');
            $table->string('house_no', 50)->nullable()->after('country');
            $table->string('road_area', 255)->nullable()->after('house_no');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['country', 'house_no', 'road_area']);
        });
    }
};
