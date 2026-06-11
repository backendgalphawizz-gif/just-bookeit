<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->decimal('price_per_day', 12, 2)->nullable()->after('description');
            $table->decimal('advance_amount', 12, 2)->nullable()->after('price_per_day');
        });

        Schema::create('portfolio_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_item_id')->constrained()->cascadeOnDelete();
            $table->string('size', 50);
            $table->string('color', 100);
            $table->decimal('price', 12, 2);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('portfolio_item_damage_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_item_id')->constrained()->cascadeOnDelete();
            $table->string('damage_type', 100);
            $table->decimal('percent', 5, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_item_damage_deductions');
        Schema::dropIfExists('portfolio_item_variants');

        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['price_per_day', 'advance_amount']);
        });
    }
};
