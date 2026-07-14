<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status', 40)->default('pending_acceptance')->after('line_amount');
            $table->string('cancellation_reason')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('cancellation_reason');
        });

        // Align existing line items with their parent sub-order status.
        DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->update([
                'order_items.status' => DB::raw("CASE
                    WHEN orders.status IN ('cancelled', 'refunded') THEN 'cancelled'
                    WHEN orders.status IN ('new', 'pending_acceptance') THEN 'pending_acceptance'
                    ELSE 'accepted'
                END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'cancellation_reason', 'responded_at']);
        });
    }
};
