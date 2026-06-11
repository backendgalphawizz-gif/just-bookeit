<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\PortfolioItem;
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
            ->with(['vendor', 'category'])
            ->whereIn('status', ['approved', 'pending']);

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

        return $this->success([
            ...CustomerApiPresenter::paginator($items, fn (PortfolioItem $item) => CustomerApiPresenter::catalogItem($item)),
            'filters' => [
                'shop_categories' => $shopCategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
                'applied' => CatalogFilter::applied($request),
            ],
        ]);
    }

    public function show(PortfolioItem $item): JsonResponse
    {
        abort_unless($item->isApprovedForCatalog(), 404);

        $item->load(['vendor', 'category']);

        $related = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->whereIn('status', ['approved', 'pending'])
            ->limit(4)
            ->get();

        return $this->success(CustomerApiPresenter::catalogDetail($item, $related));
    }
}
