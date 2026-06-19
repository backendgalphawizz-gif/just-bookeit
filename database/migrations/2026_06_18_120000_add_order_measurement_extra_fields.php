<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('measurement_type', 10)->nullable()->after('measure_waist_cm');
            $table->json('measure_extra')->nullable()->after('measurement_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['measurement_type', 'measure_extra']);
        });
    }
};
