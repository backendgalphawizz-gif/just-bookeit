<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Vendor;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsService
{
    use AppliesListDateFilter;

    protected const PER_PAGE = 15;

    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function summary(): array
    {
        return $this->dashboard->getStats();
    }

    public function chartsForReport(Request $request): array
    {
        $report = $request->string('report', 'overview')->toString();
        $admin = auth('admin')->user();
        [$rangeFrom, $rangeTo] = $this->dashboard->chartRangeFromRequest($request);
        $buckets = $this->dashboard->chartMonthBuckets($rangeFrom, $rangeTo);

        $charts = match ($report) {
            'orders' => $this->orderCharts($request, $rangeFrom, $rangeTo, $buckets),
            'refunds' => $this->refundCharts($request, $rangeFrom, $rangeTo, $buckets),
            'vendors' => $this->vendorCharts($request, $rangeFrom, $rangeTo, $buckets),
            default => $this->dashboard->getCharts($admin, $rangeFrom, $rangeTo),
        };

        return array_merge($charts, [
            'titles' => match ($report) {
                'refunds' => [
                    'monthly_revenue' => 'Refund amounts',
                    'orders_trend' => 'Refunds trend',
                ],
                'vendors' => [
                    'monthly_revenue' => 'Vendor signups',
                    'orders_trend' => 'Orders from vendors',
                ],
                default => [
                    'monthly_revenue' => 'Monthly revenue',
                    'orders_trend' => 'Orders trend',
                ],
            },
            'range_label' => $rangeFrom->format('M d, Y').' – '.$rangeTo->format('M d, Y'),
        ]);
    }

    /** @param array{labels: list<string>, keys: list<string>} $buckets */
    protected function orderCharts(Request $request, Carbon $rangeFrom, Carbon $rangeTo, array $buckets): array
    {
        $query = $this->chartScopedQuery($this->ordersReportQuery($request), $request, $rangeFrom, $rangeTo);

        $revenueByMonth = (clone $query)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, SUM(amount) as total')
            ->where('payment_status', 'success')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $ordersByMonth = (clone $query)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        return $this->mapChartBuckets($buckets, $revenueByMonth, $ordersByMonth);
    }

    /** @param array{labels: list<string>, keys: list<string>} $buckets */
    protected function refundCharts(Request $request, Carbon $rangeFrom, Carbon $rangeTo, array $buckets): array
    {
        $query = $this->chartScopedQuery($this->refundReportQuery($request), $request, $rangeFrom, $rangeTo);

        $amountByMonth = (clone $query)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, SUM(amount) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $countByMonth = (clone $query)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        return $this->mapChartBuckets($buckets, $amountByMonth, $countByMonth);
    }

    /** @param array{labels: list<string>, keys: list<string>} $buckets */
    protected function vendorCharts(Request $request, Carbon $rangeFrom, Carbon $rangeTo, array $buckets): array
    {
        $vendorQuery = $this->chartScopedQuery($this->vendorReportQuery($request), $request, $rangeFrom, $rangeTo);

        $signupsByMonth = (clone $vendorQuery)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $vendorIds = (clone $vendorQuery)->reorder()->select('id');
        $ordersQuery = $this->chartScopedQuery(
            AdminCityScope::scopeOrders(Order::query())->whereIn('vendor_id', $vendorIds),
            $request,
            $rangeFrom,
            $rangeTo
        );

        $ordersByMonth = (clone $ordersQuery)->reorder()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        return $this->mapChartBuckets($buckets, $signupsByMonth, $ordersByMonth);
    }

    protected function chartScopedQuery(
        Builder $query,
        Request $request,
        Carbon $rangeFrom,
        Carbon $rangeTo,
        string $column = 'created_at'
    ): Builder {
        if ($request->filled('from') || $request->filled('to')) {
            return $query;
        }

        return $query->whereBetween($column, [$rangeFrom, $rangeTo]);
    }

    /**
     * @param array{labels: list<string>, keys: list<string>} $buckets
     * @param \Illuminate\Support\Collection<string, mixed> $primaryByMonth
     * @param \Illuminate\Support\Collection<string, mixed> $secondaryByMonth
     *
     * @return array{monthly_revenue: array{labels: list<string>, data: list<float>}, orders_trend: array{labels: list<string>, data: list<int>}}
     */
    protected function mapChartBuckets(array $buckets, $primaryByMonth, $secondaryByMonth): array
    {
        return [
            'monthly_revenue' => [
                'labels' => $buckets['labels'],
                'data' => collect($buckets['keys'])
                    ->map(fn (string $key) => (float) ($primaryByMonth[$key] ?? 0))
                    ->values()
                    ->all(),
            ],
            'orders_trend' => [
                'labels' => $buckets['labels'],
                'data' => collect($buckets['keys'])
                    ->map(fn (string $key) => (int) ($secondaryByMonth[$key] ?? 0))
                    ->values()
                    ->all(),
            ],
        ];
    }

    public function ordersReport(Request $request): LengthAwarePaginator
    {
        return $this->ordersReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function ordersReportExport(Request $request): Collection
    {
        return $this->ordersReportQuery($request)->get();
    }

    protected function ordersReportQuery(Request $request): Builder
    {
        return AdminCityScope::scopeOrders(
            $this->applyDateRange(Order::query(), $request)
        )
            ->with(['customer', 'vendor', 'category'])
            ->when($request->filled('search'), fn (Builder $q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('vendor_id'), fn (Builder $q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->orderByDesc('created_at');
    }

    public function vendorReport(Request $request): LengthAwarePaginator
    {
        return $this->vendorReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function vendorReportExport(Request $request): Collection
    {
        return $this->vendorReportQuery($request)->get();
    }

    protected function vendorReportQuery(Request $request): Builder
    {
        return AdminCityScope::scopeVendors(
            $this->applyDateRange(Vendor::query(), $request)
        )
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function (Builder $q) use ($term) {
                    $q->where('brand_name', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('vendor_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('city'), fn (Builder $q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->orderByDesc('earnings');
    }

    public function refundReport(Request $request): LengthAwarePaginator
    {
        return $this->refundReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function refundReportExport(Request $request): Collection
    {
        return $this->refundReportQuery($request)->get();
    }

    protected function refundReportQuery(Request $request): Builder
    {
        $query = Refund::query();

        $admin = auth('admin')->user();
        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->whereHas('order', fn (Builder $orderQuery) => AdminCityScope::scopeOrders($orderQuery, $admin));
        }

        return $this->applyDateRange($query, $request)
            ->with(['customer', 'order.vendor'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn (Builder $q) => $q->whereIn('status', Refund::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn (Builder $q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('vendor_id'), fn (Builder $q) => $q->whereHas(
                'order',
                fn (Builder $order) => $order->where('vendor_id', $request->integer('vendor_id'))
            ))
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
            })
            ->orderByDesc('created_at');
    }

    public function customerGrowth(): int
    {
        return Customer::query()->where('created_at', '>=', now()->subDays(30))->count();
    }
}
