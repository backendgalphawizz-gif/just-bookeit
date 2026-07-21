<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_item_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('portfolio_item_variants', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('portfolio_item_variants', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable()->after('advance_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_item_variants', function (Blueprint $table) {
            if (Schema::hasColumn('portfolio_item_variants', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('portfolio_item_variants', 'advance_amount')) {
                $table->dropColumn('advance_amount');
            }
        });
    }
};
