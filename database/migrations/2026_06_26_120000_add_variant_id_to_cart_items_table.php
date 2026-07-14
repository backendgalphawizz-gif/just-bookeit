<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'portfolio_item_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('portfolio_item_variant_id')
                ->nullable()
                ->after('portfolio_item_id')
                ->constrained('portfolio_item_variants')
                ->nullOnDelete();

            $table->index(['customer_id', 'portfolio_item_id', 'portfolio_item_variant_id'], 'cart_items_customer_product_variant');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['portfolio_item_variant_id']);
            $table->dropIndex('cart_items_customer_product_variant');
            $table->dropColumn('portfolio_item_variant_id');
            $table->unique(['customer_id', 'portfolio_item_id']);
        });
    }
};
