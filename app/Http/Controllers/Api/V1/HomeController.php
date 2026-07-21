<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\NotificationInboxService;
use App\Support\Api\CatalogFilter;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\VendorProximityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __construct(
        protected NotificationInboxService $notifications
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'city' => ['nullable', 'string', 'max:100'],
        ], VendorProximityFilter::validationRules()));

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

        $customer = $request->user('sanctum');

        $city = $request->filled('city')
            ? trim($request->string('city')->toString())
            : trim((string) ($customer?->city ?? ''));
        $city = $city !== '' ? $city : null;
        $coords = VendorProximityFilter::coordinatesFromRequest($request);

        $featuredDesignersQuery = Vendor::query()
            ->active()
            ->where('is_listing_active', true)
            ->withApprovedProducts()
            ->orderByDesc('rating')
            ->limit(7);

        if ($coords) {
            VendorProximityFilter::applyOnVendorQuery(
                $featuredDesignersQuery,
                $coords['latitude'],
                $coords['longitude']
            );
        } elseif ($city) {
            CatalogFilter::applyCityOnVendorQuery($featuredDesignersQuery, $city);
        }

        $featuredDesigners = $featuredDesignersQuery->get();

        $notificationSummary = $customer
            ? [
                'unread_count' => $this->notifications->unreadCount(NotificationInboxService::TYPE_CUSTOMER, $customer->id),
                'total_count' => $this->notifications->totalCount(NotificationInboxService::TYPE_CUSTOMER),
            ]
            : [
                'unread_count' => 0,
                'total_count' => $this->notifications->totalCount(NotificationInboxService::TYPE_CUSTOMER),
            ];

        $location = [
            'label' => $city ?? 'Home',
            'city' => $city,
            'address' => $city ?? $customer?->city,
        ];

        if ($coords) {
            $location['latitude'] = $coords['latitude'];
            $location['longitude'] = $coords['longitude'];
            $location['radius_km'] = VendorProximityFilter::radiusKm();
        }

        return $this->success([
            'location' => $location,
            'filters' => array_filter([
                'city' => $city,
                ...VendorProximityFilter::appliedMeta($request),
            ]),
            'notifications' => $notificationSummary,
            'banners' => $banners->map(fn ($banner) => CustomerApiPresenter::banner($banner))->values()->all(),
            'services' => $services->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'shop_categories' => $shopCategories->map(fn ($category) => CustomerApiPresenter::category($category))->values()->all(),
            'featured_designers' => $featuredDesigners->map(fn ($vendor) => CustomerApiPresenter::designerSummary($vendor))->values()->all(),
        ]);
    }
}
