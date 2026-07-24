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
            $table->decimal('damage_amount', 12, 2)->nullable()->after('damage_note');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('damage_amount', 12, 2)->nullable()->after('damage_note');
        });

        // Legacy rows only had percent — seed amount once from that percent (no further recalculation in UI).
        if (Schema::hasColumn('order_items', 'damage_deduct_percent')) {
            DB::table('order_items')
                ->whereNotNull('damage_deduct_percent')
                ->whereNull('damage_amount')
                ->orderBy('id')
                ->each(function ($row) {
                    $amount = round(((float) $row->line_amount) * ((float) $row->damage_deduct_percent / 100), 2);
                    DB::table('order_items')->where('id', $row->id)->update(['damage_amount' => $amount]);
                });
        }

        if (Schema::hasColumn('orders', 'damage_deduct_percent')) {
            DB::table('orders')
                ->whereNotNull('damage_deduct_percent')
                ->whereNull('damage_amount')
                ->orderBy('id')
                ->each(function ($row) {
                    $amount = round(((float) $row->amount) * ((float) $row->damage_deduct_percent / 100), 2);
                    DB::table('orders')->where('id', $row->id)->update(['damage_amount' => $amount]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('damage_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('damage_amount');
        });
    }
};
