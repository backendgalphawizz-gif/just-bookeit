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
            $table->string('delivery_otp', 4)->nullable()->after('status');
        });

        // Seed OTP for items already in a delivery leg.
        $rows = DB::table('order_items')
            ->whereIn('status', ['in_progress', 're_intransit'])
            ->whereNull('delivery_otp')
            ->pluck('id');

        foreach ($rows as $id) {
            DB::table('order_items')
                ->where('id', $id)
                ->update(['delivery_otp' => (string) random_int(1000, 9999)]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('delivery_otp');
        });
    }
};
