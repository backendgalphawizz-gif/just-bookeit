<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Support\Api\CustomerApiPresenter;
use App\Support\LocationResolver;
use App\Support\ProductOptionCatalog;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;

class ConfigController extends ApiController
{
    public function index(): JsonResponse
    {
        $mainCategories = Category::query()
            ->active()
            ->main()
            ->with(['subcategories' => fn ($query) => $query->with('serviceCategory')->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->with('serviceCategory')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $serviceCategories = Category::query()
            ->active()
            ->service()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'product_categories' => $serviceCategories
                ->map(fn (Category $category) => [
                    'type' => $category->slug,
                    'label' => $category->name,
                    'id' => $category->id,
                ])
                ->values()
                ->all(),
            'service_categories' => $serviceCategories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
            'shop_categories' => $mainCategories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category, includeSubcategories: true))
                ->values()
                ->all(),
            'subcategories' => $subcategories
                ->map(fn (Category $category) => CustomerApiPresenter::category($category))
                ->values()
                ->all(),
            'product_audiences' => [
                ['key' => 'women', 'label' => 'Women'],
                ['key' => 'men', 'label' => 'Men'],
                ['key' => 'kids', 'label' => 'Kids'],
            ],
            'product_sizes' => ProductOptionCatalog::sizeNames(),
            'product_size_options' => ProductOptionCatalog::sizeApiItems(),
            'product_colors' => ProductOptionCatalog::colorNames(),
            'product_color_options' => ProductOptionCatalog::colorApiItems(),
            'portfolio_audiences' => [
                ['key' => 'women', 'label' => 'Women'],
                ['key' => 'men', 'label' => 'Men'],
                ['key' => 'kids', 'label' => 'Kids'],
            ],
            'service_types' => VendorValidationRules::SERVICE_TYPES,
            'booking_tabs' => [
                ['key' => 'new', 'label' => 'New'],
                ['key' => 'accepted', 'label' => 'Accepted'],
                ['key' => 'in_progress', 'label' => 'In Transit'],
                ['key' => 'completed', 'label' => 'Delivered'],
                ['key' => 'returned', 'label' => 'Returned'],
                ['key' => 'rework', 'label' => 'Rework'],
                ['key' => 're-in-transit', 'label' => 'Re-In Transit'],
                ['key' => 're-delivered', 'label' => 'Re-Delivered'],
                ['key' => 'cancelled', 'label' => 'Cancelled'],
            ],
            'payment_types' => [
                ['key' => 'credit', 'label' => 'Credit'],
                ['key' => 'debit', 'label' => 'Debit'],
            ],
            'locations' => LocationResolver::catalog(),
            'location_other_value' => LocationResolver::OTHER,
            'broadcasting' => \App\Support\BroadcastingConfig::clientConfig(url('/api/v2/broadcasting/auth')),
        ]);
    }
}
