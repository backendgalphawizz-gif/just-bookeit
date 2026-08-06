<?php

namespace App\Support\Web;

use App\Models\Category;
use App\Support\Api\CatalogFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BrowseCategoryQuery
{
    /** @return Collection<int, Category> */
    public static function mainWithSubs(?int $serviceCategoryId = null): Collection
    {
        $mains = Category::query()
            ->active()
            ->main()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $mains
            ->map(function (Category $main) use ($serviceCategoryId) {
                $audience = CatalogFilter::audienceFromCategory($main);
                $subs = self::subs($main->id, $serviceCategoryId, $audience);
                $main->setRelation('subcategories', $subs);

                return $main;
            })
            ->filter(fn (Category $main) => $main->subcategories->isNotEmpty())
            ->values();
    }

    /** @return Collection<int, Category> */
    public static function subs(?int $parentId = null, ?int $serviceCategoryId = null, ?string $audience = null): Collection
    {
        return Category::query()
            ->active()
            ->sub()
            ->when($parentId, fn (Builder $query) => $query->where('parent_id', $parentId))
            ->when($serviceCategoryId, fn (Builder $query) => $query->where('service_category_id', $serviceCategoryId))
            ->whereHas('portfolioItems', fn (Builder $items) => self::applyCatalogProductConstraints($items, $serviceCategoryId, $audience))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected static function applyCatalogProductConstraints(
        Builder $items,
        ?int $serviceCategoryId,
        ?string $audience
    ): Builder {
        $items->catalogListed();

        if ($serviceCategoryId !== null) {
            $items->where('category_id', $serviceCategoryId);
        } else {
            $items->whereColumn('portfolio_items.category_id', 'categories.service_category_id');
        }

        if ($audience !== null) {
            $items->where('audience', $audience);
        }

        return $items;
    }
}
