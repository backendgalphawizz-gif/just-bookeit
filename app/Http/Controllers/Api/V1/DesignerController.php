<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\VendorProximityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'search' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], VendorProximityFilter::validationRules()));

        $query = Vendor::query()->active()->where('is_listing_active', true);

        $coords = VendorProximityFilter::coordinatesFromRequest($request);

        if ($coords) {
            VendorProximityFilter::applyOnVendorQuery($query, $coords['latitude'], $coords['longitude']);
        } elseif ($request->filled('city')) {
            CatalogFilter::applyCityOnVendorQuery($query, $request->string('city')->toString());
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('brand_name', 'like', $term)
                    ->orWhere('shop_name', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        if ($request->boolean('featured')) {
            $query->orderByDesc('rating');
        } else {
            $query->orderBy('brand_name');
        }

        $designers = $query->paginate($request->integer('per_page', 12));

        return $this->success([
            ...CustomerApiPresenter::paginator($designers, fn (Vendor $vendor) => CustomerApiPresenter::designerSummary($vendor)),
            'filters' => array_filter([
                'city' => $request->filled('city') ? trim($request->string('city')->toString()) : null,
                ...VendorProximityFilter::appliedMeta($request),
            ]),
        ]);
    }

    public function show(Vendor $designer): JsonResponse
    {
        abort_unless($designer->status === 'active' && $designer->is_listing_active, 404);

        $products = $designer->portfolioItems()
            ->with(['vendor', 'category', 'subcategory.parent', 'variants'])
            ->where('status', 'approved')
            ->latest('id')
            ->limit(12)
            ->get();

        return $this->success(CustomerApiPresenter::designerDetail($designer, $products));
    }
}
