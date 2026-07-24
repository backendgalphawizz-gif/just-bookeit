<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('damage_note')->nullable()->after('driver_pickup_at');
            $table->decimal('damage_deduct_percent', 5, 2)->nullable()->after('damage_note');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['damage_note', 'damage_deduct_percent']);
        });
    }
};
