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
            $table->timestamp('delivered_at')->nullable()->after('driver_pickup_at');
        });

        // Backfill: items already delivered / beyond — use updated_at as delivery time estimate.
        DB::table('order_items')
            ->whereIn('status', ['delivered', 'rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'])
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
