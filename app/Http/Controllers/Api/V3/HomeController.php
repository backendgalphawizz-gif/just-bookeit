<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\Order;
use App\Services\Driver\DriverDashboardService;
use App\Support\Api\DriverApiPresenter;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends DriverApiController
{
    use AppliesListDateFilter;

    public function __construct(
        protected DriverDashboardService $dashboard
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        $request->validate(array_merge([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], $this->listDateRules()));

        $stats = $this->dashboard->stats($driver);
        $earnings = $this->dashboard->earnings(
            $driver,
            $request->integer('month') ?: null,
            $request->integer('year') ?: null
        );
        $cod = $this->dashboard->codSummary($driver, $request);

        $recentQuery = $this->dashboard->recentDeliveriesQuery($driver, $request);
        $recentDeliveries = $recentQuery->paginate($request->integer('per_page', 10));

        return $this->success([
            'driver' => DriverApiPresenter::driverSummary($driver),
            'stats' => DriverApiPresenter::dashboardStats($stats),
            'earnings' => $earnings,
            'cod' => $cod,
            'recent_deliveries' => DriverApiPresenter::paginator(
                $recentDeliveries,
                fn (Order $order) => DriverApiPresenter::deliverySummary($order, $driver)
            ),
        ]);
    }

    /** @return array<string, array<int, string>> */
    protected function listDateRules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
        ];
    }
}
