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
            ->limit(3)
            ->get();

        $featuredDesigners = Vendor::query()
            ->active()
            ->orderByDesc('rating')
            ->limit(7)
            ->get();

        return view('web.home.index', compact('banners', 'services', 'shopCategories', 'featuredDesigners'));
    }
}
