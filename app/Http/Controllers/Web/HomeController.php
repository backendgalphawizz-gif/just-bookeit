<?php

namespace App\Http\Controllers\Web;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\View\View;

class HomeController extends WebController
{
    public function index(): View
    {
        $banners = Banner::query()
            ->forAudience(Banner::AUDIENCE_CUSTOMER)
            ->published()
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        $services = Category::query()
            ->where('is_active', true)
            ->service()
            ->orderBy('sort_order')
            ->get();

        $shopCategories = Category::query()
            ->active()
            ->main()
            ->orderBy('sort_order')
            ->get();

        $featuredDesigners = Vendor::query()
            ->active()
            ->where('is_listing_active', true)
            ->withApprovedProducts()
            ->orderByDesc('rating')
            ->limit(7)
            ->get();

        return view('web.home.index', compact('banners', 'services', 'shopCategories', 'featuredDesigners'));
    }
}
