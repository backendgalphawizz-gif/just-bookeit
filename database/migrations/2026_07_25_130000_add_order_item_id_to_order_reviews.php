<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reviews', function (Blueprint $table) {
            // Unique index backs the FK on MySQL — drop FK first, then uniqueness.
            $table->dropForeign(['order_id']);
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_items')
                ->nullOnDelete();
            $table->unique('order_item_id');
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropUnique(['order_item_id']);
            $table->dropConstrainedForeignId('order_item_id');
            $table->dropIndex(['order_id', 'created_at']);
            $table->dropForeign(['order_id']);
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->unique('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }
};
