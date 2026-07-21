<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\VendorProximityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            ...VendorProximityFilter::validationRules(),
        ]);

        $term = '%'.$request->string('q').'%';

        $itemsQuery = PortfolioItem::query()
            ->with(['vendor', 'category', 'variants'])
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });

        CatalogFilter::applyCustomerCatalogConstraints($itemsQuery);
        CatalogFilter::applyVendorCity($itemsQuery, $request->string('city')->toString());
        VendorProximityFilter::applyToCatalogQuery($itemsQuery, $request);

        $items = $itemsQuery->latest('id')->limit(10)->get();

        $designersQuery = Vendor::query()
            ->active()
            ->where('is_listing_active', true)
            ->where(function ($q) use ($term) {
                $q->where('brand_name', 'like', $term)
                    ->orWhere('shop_name', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });

        $coords = VendorProximityFilter::coordinatesFromRequest($request);

        if ($coords) {
            VendorProximityFilter::applyOnVendorQuery($designersQuery, $coords['latitude'], $coords['longitude']);
        } elseif ($request->filled('city')) {
            CatalogFilter::applyCityOnVendorQuery($designersQuery, $request->string('city')->toString());
        }

        $designers = $designersQuery->orderByDesc('rating')->limit(6)->get();

        return $this->success([
            'query' => $request->string('q'),
            'catalog_items' => $items->map(fn ($item) => CustomerApiPresenter::catalogItem($item))->values()->all(),
            'designers' => $designers->map(fn ($vendor) => CustomerApiPresenter::designerSummary($vendor))->values()->all(),
        ]);
    }
}
