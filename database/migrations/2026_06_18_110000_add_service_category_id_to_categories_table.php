<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('service_category_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('categories')
                ->nullOnDelete();
        });

        $defaultServiceCategoryId = Category::query()
            ->where('type', Category::TYPE_SERVICE)
            ->where('slug', 'rented-dress')
            ->value('id');

        if ($defaultServiceCategoryId) {
            Category::query()
                ->where('type', Category::TYPE_SUB)
                ->whereNull('service_category_id')
                ->update(['service_category_id' => $defaultServiceCategoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_category_id');
        });
    }
};
