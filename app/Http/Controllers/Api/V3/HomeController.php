<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\Order;
use App\Services\Driver\DriverDashboardService;
use App\Support\Api\DriverApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends DriverApiController
{
    public function __construct(
        protected DriverDashboardService $dashboard
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);
        $stats = $this->dashboard->stats($driver);
        $newDeliveries = $this->dashboard->newDeliveries(5);

        return $this->success([
            'driver' => DriverApiPresenter::driverSummary($driver),
            'stats' => DriverApiPresenter::dashboardStats($stats),
            'new_deliveries' => $newDeliveries
                ->map(fn (Order $order) => DriverApiPresenter::deliverySummary($order, $driver))
                ->values()
                ->all(),
        ]);
    }
}
