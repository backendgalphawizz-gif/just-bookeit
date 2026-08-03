<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('orders', 'driver_delivery_status')) {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY driver_delivery_status ENUM(
            'accepted',
            'picked_up',
            'out_for_delivery',
            'rescheduled',
            'delivered'
        ) NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('orders', 'driver_delivery_status')) {
            return;
        }

        DB::table('orders')
            ->where('driver_delivery_status', 'delivered')
            ->update(['driver_delivery_status' => null]);

        DB::statement("ALTER TABLE orders MODIFY driver_delivery_status ENUM(
            'accepted',
            'picked_up',
            'out_for_delivery',
            'rescheduled'
        ) NULL");
    }
};
