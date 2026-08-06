<?php

namespace App\Http\Controllers\Web;

use App\Models\Banner;
use App\Models\Category;
use App\Models\OrderReview;
use App\Models\Vendor;
use App\Support\Web\BrowseCategoryQuery;
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

        $shopCategories = BrowseCategoryQuery::mainWithSubs();

        $featuredDesigners = Vendor::query()
            ->active()
            ->where('is_listing_active', true)
            ->withApprovedProducts()
            ->orderByDesc('rating')
            ->limit(7)
            ->get();

        $overallRating = $this->overallCustomerRating();

        return view('web.home.index', compact('banners', 'services', 'shopCategories', 'featuredDesigners', 'overallRating'));
    }

    /**
     * Platform-wide average from customer reviews; falls back to vendor
     * ratings, then a launch default, so the badge never shows 0.
     *
     * @return array{average: float, count: int}
     */
    protected function overallCustomerRating(): array
    {
        $count = OrderReview::query()->count();
        $average = $count > 0 ? (float) OrderReview::query()->avg('rating') : null;

        if ($average === null) {
            $average = (float) Vendor::query()->where('rating', '>', 0)->avg('rating');
        }

        if ($average <= 0) {
            $average = 4.8;
        }

        return [
            'average' => round($average, 1),
            'count' => $count,
        ];
    }
}
