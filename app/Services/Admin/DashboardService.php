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
use Carbon\Carbon;
use Illuminate\Support\Collection;
class DashboardService
{
    public function getData(Admin $admin): array
    {
        $stats = $this->stats();

        return [
            'stats' => $stats,
            'stat_cards' => $this->statCards($stats),
            'charts' => $this->charts(),
            'recent_activities' => $this->recentActivities(),
            'quick_actions' => $this->quickActions($admin),
        ];
    }

    public function getStats(): array
    {
        return $this->stats();
    }

    public function getCharts(): array
    {
        return $this->charts();
    }

    public function badgeCounts(): array
    {
        return [
            'pending_vendors' => Vendor::pending()->count(),
            'pending_drivers' => Driver::query()->where('status', 'pending')->count(),
            'open_refunds' => Refund::query()->whereIn('status', Refund::OPEN_STATUSES)->count(),
            'open_disputes' => Dispute::query()->whereIn('status', Dispute::OPEN_STATUSES)->count(),
            'orders_in_progress' => Order::query()->whereIn('status', Order::IN_PROGRESS_STATUSES)->count(),
            'new_orders' => Order::query()->where('status', 'new')->count(),
            'open_payouts' => VendorPayout::query()->whereIn('status', VendorPayout::OPEN_STATUSES)->count(),
            'pending_portfolio' => PortfolioItem::query()->where('status', PortfolioItem::PENDING_STATUS)->count(),
        ];
    }

    protected function stats(): array
    {
        return [
            'total_customers' => Customer::query()->count(),
            'total_vendors' => Vendor::query()->count(),
            'active_vendors' => Vendor::active()->count(),
            'pending_vendor_approvals' => Vendor::pending()->count(),
            'total_orders' => Order::query()->count(),
            'orders_in_progress' => Order::query()->whereIn('status', Order::IN_PROGRESS_STATUSES)->count(),
            'delivered_orders' => Order::query()->where('status', 'delivered')->count(),
            'cancelled_orders' => Order::query()->where('status', 'cancelled')->count(),
            'total_revenue' => (float) Order::query()
                ->where('payment_status', 'success')
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('amount'),
            'refund_requests' => Refund::query()->whereIn('status', Refund::OPEN_STATUSES)->count(),
        ];
    }

    protected function statCards(array $stats): array
    {
        return [
            ['key' => 'total_customers', 'label' => 'Total Customers', 'value' => number_format($stats['total_customers']), 'tone' => 'blue'],
            ['key' => 'total_vendors', 'label' => 'Total Vendors', 'value' => number_format($stats['total_vendors']), 'tone' => 'violet'],
            ['key' => 'active_vendors', 'label' => 'Active Vendors', 'value' => number_format($stats['active_vendors']), 'tone' => 'emerald'],
            ['key' => 'pending_vendor_approvals', 'label' => 'Pending Vendor Approvals', 'value' => number_format($stats['pending_vendor_approvals']), 'tone' => 'amber'],
            ['key' => 'total_orders', 'label' => 'Total Orders', 'value' => number_format($stats['total_orders']), 'tone' => 'slate'],
            ['key' => 'orders_in_progress', 'label' => 'Orders In Progress', 'value' => number_format($stats['orders_in_progress']), 'tone' => 'sky'],
            ['key' => 'delivered_orders', 'label' => 'Delivered Orders', 'value' => number_format($stats['delivered_orders']), 'tone' => 'teal'],
            ['key' => 'cancelled_orders', 'label' => 'Cancelled Orders', 'value' => number_format($stats['cancelled_orders']), 'tone' => 'rose'],
            ['key' => 'total_revenue', 'label' => 'Total Revenue', 'value' => '₹'.number_format($stats['total_revenue'], 2), 'tone' => 'indigo'],
            ['key' => 'refund_requests', 'label' => 'Refund Requests', 'value' => number_format($stats['refund_requests']), 'tone' => 'orange'],
        ];
    }

    protected function charts(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i)->startOfMonth());
        $labels = $months->map(fn (Carbon $m) => $m->format('M Y'))->values()->all();

        $revenueByMonth = Order::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, SUM(amount) as total')
            ->where('payment_status', 'success')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('created_at', '>=', $months->first())
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $ordersByMonth = Order::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->where('created_at', '>=', $months->first())
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $vendorsByMonth = Vendor::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->where('created_at', '>=', $months->first())
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $monthKeys = $months->map(fn (Carbon $m) => $m->format('Y-m'))->values();

        $categoryBookings = Order::query()
            ->join('categories', 'orders.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as label, COUNT(orders.id) as total')
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

    protected function recentActivities(): Collection
    {
        $activities = collect();

        Vendor::query()->latest()->limit(5)->get()->each(function (Vendor $vendor) use ($activities) {
            $activities->push([
                'type' => $vendor->status === 'pending' ? 'vendor_signup' : 'vendor_approval',
                'title' => $vendor->status === 'pending' ? 'New vendor signup' : 'Vendor approved',
                'description' => "{$vendor->brand_name} · {$vendor->city}",
                'occurred_at' => $vendor->approved_at ?? $vendor->created_at,
                'tone' => $vendor->status === 'pending' ? 'amber' : 'emerald',
            ]);
        });

        Order::query()->with('customer')->latest()->limit(8)->get()->each(function (Order $order) use ($activities) {
            $activities->push([
                'type' => 'new_order',
                'title' => 'New order',
                'description' => "{$order->order_number} · {$order->customer->name} · ₹".number_format($order->amount, 2),
                'occurred_at' => $order->created_at,
                'tone' => 'blue',
            ]);
        });

        Refund::query()->with('customer')->latest()->limit(5)->get()->each(function (Refund $refund) use ($activities) {
            $activities->push([
                'type' => 'refund_request',
                'title' => 'Refund request',
                'description' => "{$refund->customer->name} · {$refund->status} · ₹".number_format($refund->amount, 2),
                'occurred_at' => $refund->created_at,
                'tone' => 'orange',
            ]);
        });

        Dispute::query()->with('order')->latest()->limit(5)->get()->each(function (Dispute $dispute) use ($activities) {
            $activities->push([
                'type' => 'dispute',
                'title' => 'Dispute raised',
                'description' => "{$dispute->order->order_number} · {$dispute->subject}",
                'occurred_at' => $dispute->created_at,
                'tone' => 'rose',
            ]);
        });

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
        $badges = $this->badgeCounts();

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
}
