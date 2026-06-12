<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\NotificationInboxService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __construct(
        protected NotificationInboxService $notifications
    ) {}

    public function index(Request $request): JsonResponse
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
            ->limit(10)
            ->get();

        $featuredDesigners = Vendor::query()
            ->active()
            ->orderByDesc('rating')
            ->limit(7)
            ->get();

        $customer = $request->user('sanctum');

        $notificationSummary = $customer
            ? [
                'unread_count' => $this->notifications->unreadCount(NotificationInboxService::TYPE_CUSTOMER, $customer->id),
                'total_count' => $this->notifications->totalCount(NotificationInboxService::TYPE_CUSTOMER),
            ]
            : [
                'unread_count' => 0,
                'total_count' => $this->notifications->totalCount(NotificationInboxService::TYPE_CUSTOMER),
            ];

        return $this->success([
            'location' => [
                'label' => 'Home',
                'address' => $customer?->city,
            ],
            'notifications' => $notificationSummary,
            'banners' => $banners->map(fn ($banner) => CustomerApiPresenter::banner($banner))->values()->all(),
            'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'shop_categories' => $shopCategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'featured_designers' => $featuredDesigners->map(fn ($vendor) => CustomerApiPresenter::designerSummary($vendor))->values()->all(),
        ]);
    }
}
