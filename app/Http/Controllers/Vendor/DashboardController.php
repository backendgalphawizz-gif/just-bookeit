<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Banner;
use App\Services\Vendor\VendorDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends VendorController
{
    public function __construct(
        protected VendorDashboardService $dashboard
    ) {}

    public function index(Request $request): View
    {
        $vendor = $this->vendor();
        $date = $request->filled('date') ? Carbon::parse($request->string('date')) : now();

        return view('vendor.dashboard.index', [
            'vendor' => $vendor,
            'promoBanner' => Banner::query()
                ->forAudience(Banner::AUDIENCE_VENDOR)
                ->published()
                ->latest('id')
                ->first(),
            'stats' => $this->dashboard->stats($vendor),
            'schedule' => $this->dashboard->deliverySchedule($vendor, $date),
            'recentBookings' => $this->dashboard->recentBookings($vendor),
            'scheduleDate' => $date,
        ]);
    }
}
