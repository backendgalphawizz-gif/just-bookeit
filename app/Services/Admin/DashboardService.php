<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Refund;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Support\AdminCityScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getData(Admin $admin, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $stats = $this->stats($admin);
        [$rangeFrom, $rangeTo] = $this->resolveChartRange($from, $to);

        return [
            'stats' => $stats,
            'stat_cards' => $this->statCards($stats, $admin),
            'charts' => $this->charts($admin, $rangeFrom, $rangeTo),
            'analytics_from' => $rangeFrom->toDateString(),
            'analytics_to' => $rangeTo->toDateString(),
            'analytics_range_label' => $this->chartRangeLabel($from, $to, $rangeFrom, $rangeTo),
            'chart_visibility' => [
                'monthly_revenue' => $admin->hasPermission('payments', 'view') || $admin->hasPermission('orders', 'view'),
                'orders_trend' => $admin->hasPermission('orders', 'view'),
                'vendor_growth' => $admin->hasPermission('vendors', 'view'),
                'category_bookings' => $admin->hasPermission('orders', 'view'),
            ],
            'recent_activities' => $this->recentActivities($admin),
            'quick_actions' => $this->quickActions($admin),
        ];
    }

    public function getStats(?Admin $admin = null): array
    {
        return $this->stats($admin);
    }

    public function getCharts(?Admin $admin = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        [$rangeFrom, $rangeTo] = $this->resolveChartRange($from, $to);

        return $this->charts($admin, $rangeFrom, $rangeTo);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function chartRangeFromRequest(\Illuminate\Http\Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->date('from'))->startOfDay()
            : null;
        $to = $request->filled('to')
            ? Carbon::parse($request->date('to'))->endOfDay()
            : null;

        return $this->resolveChartRange($from, $to);
    }

    /** @return array{labels: list<string>, keys: list<string>} */
    public function chartMonthBuckets(Carbon $rangeFrom, Carbon $rangeTo): array
    {
        $months = $this->chartMonths($rangeFrom, $rangeTo);

        return [
            'labels' => $months->map(fn (Carbon $m) => $m->format('M Y'))->values()->all(),
            'keys' => $months->map(fn (Carbon $m) => $m->format('Y-m'))->values()->all(),
        ];
    }

    /** @return array<string, int> */
    public function badgeCounts(?Admin $admin = null): array
    {
        $vendorQuery = $this->scopedVendors($admin);
        $driverQuery = $this->scopedDrivers($admin);
        $orderQuery = $this->scopedOrders($admin);
        $refundQuery = $this->scopedRefunds($admin);
        $disputeQuery = $this->scopedDisputes($admin);
        $portfolioQuery = $this->scopedPortfolio($admin);
        $payoutQuery = VendorPayout::query();

        return [
            'pending_vendors' => (clone $vendorQuery)->pending()->count(),
            'pending_drivers' => (clone $driverQuery)->where('status', 'pending')->count(),
            'open_refunds' => (clone $refundQuery)->whereIn('status', Refund::OPEN_STATUSES)->count(),
            'open_disputes' => (clone $disputeQuery)->whereIn('status', Dispute::OPEN_STATUSES)->count(),
            'orders_in_progress' => (clone $orderQuery)->whereIn('status', Order::IN_PROGRESS_STATUSES)->count(),
            'new_orders' => (clone $orderQuery)->whereNull('checkout_order_id')->where('status', 'new')->count(),
            'open_payouts' => (clone $payoutQuery)->whereIn('status', VendorPayout::OPEN_STATUSES)->count(),
            'pending_portfolio' => (clone $portfolioQuery)->where('status', PortfolioItem::PENDING_STATUS)->count(),
        ];
    }

    /** @return array<string, float|int> */
    protected function stats(?Admin $admin): array
    {
        $customerQuery = $this->scopedCustomers($admin);
        $vendorQuery = $this->scopedVendors($admin);
        $orderQuery = $this->scopedOrders($admin);
        $refundQuery = $this->scopedRefunds($admin);

        return [
            'total_customers' => (clone $customerQuery)->count(),
            'total_vendors' => (clone $vendorQuery)->count(),
            'active_vendors' => (clone $vendorQuery)->active()->count(),
            'pending_vendor_approvals' => (clone $vendorQuery)->pending()->count(),
            'total_orders' => (clone $orderQuery)->count(),
            'orders_in_progress' => (clone $orderQuery)->whereIn('status', Order::IN_PROGRESS_STATUSES)->count(),
            'delivered_orders' => (clone $orderQuery)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $orderQuery)->where('status', 'cancelled')->count(),
            'total_revenue' => (float) (clone $orderQuery)
                ->where('payment_status', 'success')
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('amount'),
            'refund_requests' => (clone $refundQuery)->whereIn('status', Refund::OPEN_STATUSES)->count(),
        ];
    }

    /** @param array<string, float|int> $stats */
    protected function statCards(array $stats, Admin $admin): array
    {
        $cards = [
            ['key' => 'total_customers', 'label' => 'Total Customers', 'value' => number_format($stats['total_customers']), 'tone' => 'blue', 'permission' => 'customers'],
            ['key' => 'total_vendors', 'label' => 'Total Vendors', 'value' => number_format($stats['total_vendors']), 'tone' => 'violet', 'permission' => 'vendors'],
            ['key' => 'active_vendors', 'label' => 'Active Vendors', 'value' => number_format($stats['active_vendors']), 'tone' => 'emerald', 'permission' => 'vendors'],
            ['key' => 'pending_vendor_approvals', 'label' => 'Pending Vendor Approvals', 'value' => number_format($stats['pending_vendor_approvals']), 'tone' => 'amber', 'permission' => 'vendors'],
            ['key' => 'total_orders', 'label' => 'Total Orders', 'value' => number_format($stats['total_orders']), 'tone' => 'slate', 'permission' => 'orders'],
            ['key' => 'orders_in_progress', 'label' => 'Orders In Progress', 'value' => number_format($stats['orders_in_progress']), 'tone' => 'sky', 'permission' => 'orders'],
            ['key' => 'delivered_orders', 'label' => 'Delivered Orders', 'value' => number_format($stats['delivered_orders']), 'tone' => 'teal', 'permission' => 'orders'],
            ['key' => 'cancelled_orders', 'label' => 'Cancelled Orders', 'value' => number_format($stats['cancelled_orders']), 'tone' => 'rose', 'permission' => 'orders'],
            ['key' => 'total_revenue', 'label' => 'Total Revenue', 'value' => '₹'.number_format($stats['total_revenue'], 2), 'tone' => 'indigo', 'permission' => 'payments'],
            ['key' => 'refund_requests', 'label' => 'Refund Requests', 'value' => number_format($stats['refund_requests']), 'tone' => 'orange', 'permission' => 'refunds'],
        ];

        return collect($cards)
            ->filter(fn (array $card) => $admin->hasPermission($card['permission'], 'view'))
            ->map(fn (array $card) => collect($card)->except('permission')->all())
            ->values()
            ->all();
    }

    protected function charts(?Admin $admin, Carbon $rangeFrom, Carbon $rangeTo): array
    {
        $months = $this->chartMonths($rangeFrom, $rangeTo);
        $labels = $months->map(fn (Carbon $m) => $m->format('M Y'))->values()->all();
        $monthKeys = $months->map(fn (Carbon $m) => $m->format('Y-m'))->values();

        $orderQuery = $this->scopedOrders($admin);
        $vendorQuery = $this->scopedVendors($admin);

        $revenueByMonth = (clone $orderQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, SUM(amount) as total')
            ->where('payment_status', 'success')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereBetween('created_at', [$rangeFrom, $rangeTo])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $ordersByMonth = (clone $orderQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->whereBetween('created_at', [$rangeFrom, $rangeTo])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $vendorsByMonth = (clone $vendorQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->whereBetween('created_at', [$rangeFrom, $rangeTo])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $categoryBookings = (clone $orderQuery)
            ->join('categories', 'orders.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as label, COUNT(orders.id) as total')
            ->whereBetween('orders.created_at', [$rangeFrom, $rangeTo])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'monthly_revenue' => [
                'labels' => $labels,
                'data' => $monthKeys->map(fn (string $key) => (float) ($revenueByMonth[$key] ?? 0))->values()->all(),
            ],
            'orders_trend' => [
                'labels' => $labels,
                'data' => $monthKeys->map(fn (string $key) => (int) ($ordersByMonth[$key] ?? 0))->values()->all(),
            ],
            'vendor_growth' => [
                'labels' => $labels,
                'data' => $monthKeys->map(fn (string $key) => (int) ($vendorsByMonth[$key] ?? 0))->values()->all(),
            ],
            'category_bookings' => [
                'labels' => $categoryBookings->pluck('label')->all(),
                'data' => $categoryBookings->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function resolveChartRange(?Carbon $from, ?Carbon $to): array
    {
        if (! $from && ! $to) {
            return [
                now()->copy()->subMonths(5)->startOfMonth()->startOfDay(),
                now()->copy()->endOfDay(),
            ];
        }

        $rangeTo = ($to ?? now())->copy()->endOfDay();
        $rangeFrom = $from
            ? $from->copy()->startOfDay()
            : $rangeTo->copy()->subMonths(5)->startOfMonth()->startOfDay();

        if ($rangeFrom->gt($rangeTo)) {
            return [$rangeTo->copy()->startOfDay(), $rangeFrom->copy()->endOfDay()];
        }

        return [$rangeFrom, $rangeTo];
    }

    /** @return Collection<int, Carbon> */
    protected function chartMonths(Carbon $rangeFrom, Carbon $rangeTo): Collection
    {
        $months = collect();
        $cursor = $rangeFrom->copy()->startOfMonth();
        $endMonth = $rangeTo->copy()->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months;
    }

    protected function chartRangeLabel(?Carbon $requestFrom, ?Carbon $requestTo, Carbon $rangeFrom, Carbon $rangeTo): string
    {
        if (! $requestFrom && ! $requestTo) {
            return 'Last 6 months · '.$rangeFrom->format('M Y').' – '.$rangeTo->format('M Y');
        }

        return $rangeFrom->format('M d, Y').' – '.$rangeTo->format('M d, Y');
    }

    protected function recentActivities(?Admin $admin): Collection
    {
        $activities = collect();

        if ($admin?->hasPermission('vendors', 'view')) {
            $this->scopedVendors($admin)->latest()->limit(5)->get()->each(function (Vendor $vendor) use ($activities) {
                $activities->push([
                    'type' => $vendor->status === 'pending' ? 'vendor_signup' : 'vendor_approval',
                    'title' => $vendor->status === 'pending' ? 'New vendor signup' : 'Vendor approved',
                    'description' => "{$vendor->brand_name} · {$vendor->city}",
                    'occurred_at' => $vendor->approved_at ?? $vendor->created_at,
                    'tone' => $vendor->status === 'pending' ? 'amber' : 'emerald',
                ]);
            });
        }

        if ($admin?->hasPermission('orders', 'view')) {
            $this->scopedOrders($admin)->with('customer')->latest('id')->limit(8)->get()->each(function (Order $order) use ($activities) {
                $activities->push([
                    'type' => 'new_order',
                    'title' => 'New order',
                    'description' => "{$order->order_number} · {$order->customer->name} · ₹".number_format($order->amount, 2),
                    'occurred_at' => $order->created_at,
                    'tone' => 'blue',
                ]);
            });
        }

        if ($admin?->hasPermission('refunds', 'view')) {
            $this->scopedRefunds($admin)->with('customer')->latest()->limit(5)->get()->each(function (Refund $refund) use ($activities) {
                $activities->push([
                    'type' => 'refund_request',
                    'title' => 'Refund request',
                    'description' => "{$refund->customer->name} · {$refund->status} · ₹".number_format($refund->amount, 2),
                    'occurred_at' => $refund->created_at,
                    'tone' => 'orange',
                ]);
            });
        }

        if ($admin?->hasPermission('disputes', 'view')) {
            $this->scopedDisputes($admin)->with('order')->latest()->limit(5)->get()->each(function (Dispute $dispute) use ($activities) {
                $activities->push([
                    'type' => 'dispute',
                    'title' => 'Dispute raised',
                    'description' => "{$dispute->order->order_number} · {$dispute->subject}",
                    'occurred_at' => $dispute->created_at,
                    'tone' => 'rose',
                ]);
            });
        }

        return $activities
            ->sortByDesc('occurred_at')
            ->take(12)
            ->values()
            ->map(function (array $item) {
                $item['time_ago'] = Carbon::parse($item['occurred_at'])->diffForHumans();

                return $item;
            });
    }

    protected function quickActions(Admin $admin): array
    {
        $badges = $this->badgeCounts($admin);

        $actions = [
            [
                'label' => 'Approve Vendors',
                'description' => 'Review pending designer registrations',
                'permission' => 'vendors',
                'count' => $badges['pending_vendors'],
                'route' => route('admin.vendors.index', ['status' => 'pending']),
            ],
            [
                'label' => 'Manage Orders',
                'description' => 'View and update order lifecycle',
                'permission' => 'orders',
                'count' => $badges['orders_in_progress'],
                'route' => route('admin.orders.index'),
            ],
            [
                'label' => 'Create Banner',
                'description' => 'Add or schedule homepage banners',
                'permission' => 'banners',
                'count' => null,
                'route' => route('admin.banners.create'),
            ],
            [
                'label' => 'Process Refund',
                'description' => 'Review open refund requests',
                'permission' => 'refunds',
                'count' => $badges['open_refunds'],
                'route' => route('admin.refunds.index', ['status' => '_open_']),
            ],
        ];

        return collect($actions)
            ->filter(fn (array $action) => $admin->hasPermission($action['permission'], 'view'))
            ->values()
            ->all();
    }

    /** @return Builder<Vendor> */
    protected function scopedVendors(?Admin $admin): Builder
    {
        return AdminCityScope::scopeVendors(Vendor::query(), $admin);
    }

    /** @return Builder<Driver> */
    protected function scopedDrivers(?Admin $admin): Builder
    {
        return AdminCityScope::scopeDrivers(Driver::query(), $admin);
    }

    /** @return Builder<Customer> */
    protected function scopedCustomers(?Admin $admin): Builder
    {
        return AdminCityScope::scopeCustomers(Customer::query(), $admin);
    }

    /** @return Builder<Order> */
    protected function scopedOrders(?Admin $admin): Builder
    {
        return AdminCityScope::scopeOrders(Order::query(), $admin);
    }

    /** @return Builder<Refund> */
    protected function scopedRefunds(?Admin $admin): Builder
    {
        $query = Refund::query();

        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->whereHas('order', fn (Builder $orderQuery) => AdminCityScope::scopeOrders($orderQuery, $admin));
        }

        return $query;
    }

    /** @return Builder<Dispute> */
    protected function scopedDisputes(?Admin $admin): Builder
    {
        $query = Dispute::query();

        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->whereHas('order', fn (Builder $orderQuery) => AdminCityScope::scopeOrders($orderQuery, $admin));
        }

        return $query;
    }

    /** @return Builder<PortfolioItem> */
    protected function scopedPortfolio(?Admin $admin): Builder
    {
        $query = PortfolioItem::query();

        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->whereHas('vendor', fn (Builder $vendorQuery) => AdminCityScope::scopeVendors($vendorQuery, $admin));
        }

        return $query;
    }
}
