<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'driver_delivery_proof_path')) {
                $table->string('driver_delivery_proof_path')->nullable()->after('cod_collected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'driver_delivery_proof_path')) {
                $table->dropColumn('driver_delivery_proof_path');
            }
        });
    }
};
