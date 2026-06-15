<?php

use App\Models\Category;
use App\Models\PortfolioItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('main', 'service', 'sub') NOT NULL");
        }

        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->constrained('categories')
                ->nullOnDelete();
        });

        $this->seedDefaultSubcategories();
        $this->backfillProductSubcategories();
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
        });

        Category::query()->where('type', Category::TYPE_SUB)->delete();

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('main', 'service') NOT NULL");
        }
    }

    protected function seedDefaultSubcategories(): void
    {
        $definitions = [
            'women' => ['Sarees', 'Lehengas', 'Gowns', 'Kurtis'],
            'men' => ['Suits', 'Kurtas', 'Sherwanis'],
            'kids' => ['Ethnic Wear', 'Party Wear'],
        ];

        foreach ($definitions as $mainSlug => $names) {
            $main = Category::query()->where('type', Category::TYPE_MAIN)->where('slug', $mainSlug)->first();

            if (! $main) {
                continue;
            }

            foreach ($names as $index => $name) {
                $slug = $mainSlug.'-'.str($name)->slug();

                Category::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'type' => Category::TYPE_SUB,
                        'parent_id' => $main->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    protected function backfillProductSubcategories(): void
    {
        $defaultSubByAudience = Category::query()
            ->where('type', Category::TYPE_SUB)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Category $sub) => $sub->parent?->slug)
            ->map(fn ($group) => $group->first()?->id);

        PortfolioItem::query()
            ->whereNull('subcategory_id')
            ->orderBy('id')
            ->each(function (PortfolioItem $item) use ($defaultSubByAudience): void {
                $audience = $item->audience ?? 'women';
                $subcategoryId = $defaultSubByAudience->get($audience);

                if ($subcategoryId) {
                    $item->update(['subcategory_id' => $subcategoryId]);
                }
            });
    }
};
