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
        $earningsMonth = $request->filled('earnings_month')
            ? Carbon::parse($request->string('earnings_month').'-01')
            : now();

        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekDays = collect(range(0, 6))->map(fn (int $offset) => $weekStart->copy()->addDays($offset));

        return view('vendor.dashboard.index', [
            'vendor' => $vendor,
            'promoBanner' => Banner::query()
                ->forAudience(Banner::AUDIENCE_VENDOR)
                ->published()
                ->latest('id')
                ->first(),
            'stats' => $this->dashboard->stats($vendor, $earningsMonth),
            'schedule' => $this->dashboard->deliverySchedule($vendor, $date),
            'recentBookings' => $this->dashboard->recentBookings($vendor, 6),
            'scheduleDate' => $date,
            'weekDays' => $weekDays,
            'earningsMonth' => $earningsMonth,
        ]);
    }
}