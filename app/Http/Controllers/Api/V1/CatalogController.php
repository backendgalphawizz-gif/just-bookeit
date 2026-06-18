<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'search' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], CatalogFilter::validationRules()));

        $query = PortfolioItem::query()
            ->with(['vendor', 'category', 'subcategory.parent']);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('brand_name', 'like', $term));
            });
        }

        CatalogFilter::applyToQuery($query, $request);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        $items = $query->latest('id')->paginate($request->integer('per_page', 12));

        $shopCategories = Category::query()
            ->where('is_active', true)
            ->where('type', Category::TYPE_MAIN)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $services = Category::query()
            ->where('is_active', true)
            ->where('type', Category::TYPE_SERVICE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $mainCategoryId = CatalogFilter::resolveMainCategoryId($request);

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->when($mainCategoryId, fn ($query) => $query->where('parent_id', $mainCategoryId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cities = Vendor::query()
            ->where('status', 'active')
            ->where('is_listing_active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->whereHas('portfolioItems', fn ($portfolio) => $portfolio->where('status', 'approved'))
            ->orderBy('city')
            ->distinct()
            ->pluck('city')
            ->values()
            ->all();

        return $this->success([
            ...CustomerApiPresenter::paginator($items, fn (PortfolioItem $item) => CustomerApiPresenter::catalogItem($item)),
            'filters' => [
                'shop_categories' => $shopCategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'subcategories' => $subcategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'cities' => $cities,
                'applied' => CatalogFilter::applied($request),
            ],
        ]);
    }

    public function show(PortfolioItem $item): JsonResponse
    {
        abort_unless($item->status === 'approved', 404);
        abort_unless($item->vendor && $item->vendor->status === 'active' && $item->vendor->is_listing_active, 404);

        $item->load(['vendor', 'category', 'subcategory.parent']);

        $related = PortfolioItem::query()
            ->with(['vendor', 'category', 'subcategory.parent'])
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->where('status', 'approved')
            ->limit(4)
            ->get();

        return $this->success(CustomerApiPresenter::catalogDetail($item, $related));
    }
}
