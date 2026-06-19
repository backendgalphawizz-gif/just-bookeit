<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'portfolio_item_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('portfolio_item_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('orders', 'subcategory_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $after = Schema::hasColumn('orders', 'portfolio_item_id') ? 'portfolio_item_id' : 'category_id';

                $table->foreignId('subcategory_id')
                    ->nullable()
                    ->after($after)
                    ->constrained('categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'subcategory_id')) {
                $table->dropConstrainedForeignId('subcategory_id');
            }

            if (Schema::hasColumn('orders', 'portfolio_item_id')) {
                $table->dropConstrainedForeignId('portfolio_item_id');
            }
        });
    }
};
