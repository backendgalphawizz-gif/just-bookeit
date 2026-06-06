<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest('id')
            ->limit(5)
            ->get();

        $services = Category::query()
            ->where('is_active', true)
            ->where('type', 'service')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $shopCategories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $featuredDesigners = Vendor::query()
            ->active()
            ->orderByDesc('rating')
            ->limit(7)
            ->get();

        $customer = $request->user('sanctum');

        return $this->success([
            'location' => [
                'label' => 'Home',
                'address' => $customer?->city,
            ],
            'banners' => $banners->map(fn ($banner) => CustomerApiPresenter::banner($banner))->values()->all(),
            'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'shop_categories' => $shopCategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'featured_designers' => $featuredDesigners->map(fn ($vendor) => CustomerApiPresenter::designerSummary($vendor))->values()->all(),
        ]);
    }
}
