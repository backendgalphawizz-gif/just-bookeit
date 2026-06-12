<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Banner;
use App\Models\Order;
use App\Services\NotificationInboxService;
use App\Services\Vendor\VendorDashboardService;
use App\Support\Api\VendorApiPresenter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends VendorApiController
{
    public function __construct(
        protected VendorDashboardService $dashboard,
        protected NotificationInboxService $notifications
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $date = $request->filled('date') ? Carbon::parse($request->string('date')) : now();
        $stats = $this->dashboard->stats($vendor);

        $newBookings = Order::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['new', 'pending_acceptance'])
            ->with(['customer', 'category'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $promoBanner = Banner::query()
            ->forAudience(Banner::AUDIENCE_VENDOR)
            ->published()
            ->latest('id')
            ->first();

        $unreadChats = $vendor->conversations()
            ->whereHas('messages', fn ($q) => $q->where('sender_type', 'customer')->whereNull('read_at'))
            ->count();

        return $this->success([
            'vendor' => VendorApiPresenter::vendorSummary($vendor),
            'notifications' => [
                'unread_count' => $this->notifications->unreadCount(NotificationInboxService::TYPE_VENDOR, $vendor->id),
                'total_count' => $this->notifications->totalCount(NotificationInboxService::TYPE_VENDOR),
                'unread_chats' => $unreadChats,
                'new_bookings' => $newBookings->count(),
            ],
            'order_stats' => VendorApiPresenter::orderStats($stats),
            'earnings' => VendorApiPresenter::earningsSummary($stats),
            'delivery_schedule' => [
                'selected_date' => $date->toDateString(),
                'items' => $this->dashboard->deliverySchedule($vendor, $date)
                    ->map(fn (Order $order) => VendorApiPresenter::scheduleItem($order))
                    ->values()
                    ->all(),
            ],
            'new_bookings' => $newBookings
                ->map(fn (Order $order) => VendorApiPresenter::bookingSummary($order))
                ->values()
                ->all(),
            'promo_banner' => VendorApiPresenter::promoBanner($promoBanner),
        ]);
    }
}
