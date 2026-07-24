<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Align orders.status with Order::STATUSES (rental_active + completed were missing).
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'new',
            'pending_acceptance',
            'accepted',
            'in_progress',
            'delivered',
            'rental_active',
            'rework',
            're_intransit',
            'returned',
            're_delivered',
            'completed',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'new'");

        if (Schema::hasTable('order_items')) {
            DB::statement("ALTER TABLE order_items MODIFY status ENUM(
                'pending_acceptance',
                'accepted',
                'in_progress',
                'delivered',
                'rental_active',
                'rework',
                're_intransit',
                'returned',
                're_delivered',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending_acceptance'");
        }
    }

    public function down(): void
    {
        DB::table('orders')->whereIn('status', ['rental_active', 'completed'])->update(['status' => 'delivered']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'new',
            'pending_acceptance',
            'accepted',
            'in_progress',
            'delivered',
            'returned',
            'rework',
            're_intransit',
            're_delivered',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'new'");

        if (Schema::hasTable('order_items')) {
            DB::table('order_items')->where('status', 'completed')->update(['status' => 'returned']);
            DB::table('order_items')->where('status', 'rental_active')->update(['status' => 'delivered']);

            DB::statement("ALTER TABLE order_items MODIFY status ENUM(
                'pending_acceptance',
                'accepted',
                'in_progress',
                'delivered',
                'rental_active',
                'rework',
                're_intransit',
                'returned',
                're_delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'pending_acceptance'");
        }
    }
};
