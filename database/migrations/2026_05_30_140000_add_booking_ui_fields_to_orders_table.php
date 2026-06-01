<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('item_image_path')->nullable()->after('item_description');
            $table->json('reference_image_paths')->nullable()->after('item_image_path');
            $table->text('billing_address')->nullable()->after('delivery_address');
            $table->decimal('tax_amount', 12, 2)->nullable()->after('delivery_fee');
            $table->string('damage_note')->nullable()->after('admin_notes');
            $table->decimal('damage_deduct_percent', 5, 2)->nullable()->after('damage_note');
            $table->unsignedSmallInteger('measure_height_cm')->nullable()->after('damage_deduct_percent');
            $table->unsignedSmallInteger('measure_chest_cm')->nullable()->after('measure_height_cm');
            $table->unsignedSmallInteger('measure_waist_cm')->nullable()->after('measure_chest_cm');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'item_image_path',
                'reference_image_paths',
                'billing_address',
                'tax_amount',
                'damage_note',
                'damage_deduct_percent',
                'measure_height_cm',
                'measure_chest_cm',
                'measure_waist_cm',
            ]);
        });
    }
};
