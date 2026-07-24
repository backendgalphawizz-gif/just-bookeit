<?php

namespace App\Services\Driver;

use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Support\Api\DriverDeliveryTab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DriverDashboardService
{
    public function stats(Driver $driver): array
    {
        $base = Order::query()->where(function (Builder $builder) use ($driver) {
            $builder->where('driver_id', $driver->id)
                ->orWhereHas('orderItems', fn (Builder $items) => $items->where('driver_id', $driver->id));
        });
        $dispatchStatuses = DriverDeliveryTab::activeDeliveryStatuses();

        return [
            'total_earnings' => round((float) $driver->total_earnings, 2),
            'wallet_balance' => round((float) $driver->wallet_balance, 2),
            'assigned_deliveries' => (clone $base)
                ->where(function (Builder $builder) use ($dispatchStatuses) {
                    $builder->whereIn('status', $dispatchStatuses)
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', $dispatchStatuses));
                })
                ->count(),
            'pending_deliveries' => Order::query()
                ->where(function (Builder $builder) use ($dispatchStatuses) {
                    $builder->whereIn('status', $dispatchStatuses)
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', $dispatchStatuses));
                })
                ->whereNull('driver_id')
                ->whereDoesntHave('orderItems', fn (Builder $items) => $items
                    ->whereIn('status', $dispatchStatuses)
                    ->whereNotNull('driver_id'))
                ->count(),
            'completed_deliveries' => (clone $base)
                ->where(function (Builder $builder) {
                    $builder->whereIn('status', ['delivered', 're_delivered'])
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', ['delivered', 're_delivered']));
                })
                ->count(),
            'cancelled_deliveries' => (clone $base)
                ->where(function (Builder $builder) {
                    $builder->where('status', 'cancelled')
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->where('status', 'cancelled'));
                })
                ->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function earnings(Driver $driver, ?int $month = null, ?int $year = null): array
    {
        $month ??= (int) now()->month;
        $year ??= (int) now()->year;

        $monthStart = now()->setDate($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $yearStart = now()->setDate($year, 1, 1)->startOfMonth();

        $monthTotal = DriverWalletTransaction::query()
            ->where('driver_id', $driver->id)
            ->where('direction', 'credit')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $ytdTotal = DriverWalletTransaction::query()
            ->where('driver_id', $driver->id)
            ->where('direction', 'credit')
            ->where('created_at', '>=', $yearStart)
            ->where('created_at', '<=', $monthEnd)
            ->sum('amount');

        return [
            'month' => $month,
            'year' => $year,
            'month_label' => $monthStart->format('F Y'),
            'this_month' => round((float) $monthTotal, 2),
            'this_month_label' => '₹'.number_format((float) $monthTotal, 0),
            'ytd' => round((float) $ytdTotal, 2),
            'ytd_label' => '₹'.number_format((float) $ytdTotal, 0),
            'currency' => 'INR',
            'last_updated_at' => now()->format('M d, Y, g:i A'),
            'last_updated_at_iso' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public function codSummary(Driver $driver, ?Request $request = null): array
    {
        $query = Order::query()
            ->where('driver_id', $driver->id)
            ->where('payment_method', 'cod')
            ->whereNotNull('cod_collected_at');

        $this->applyDateFilter($query, $request);

        $total = (float) (clone $query)->get()->sum(fn (Order $order) => $order->grandTotal());

        return [
            'total_collected' => round($total, 2),
            'total_collected_label' => '₹'.number_format($total, 0),
            'currency' => 'INR',
            'orders_count' => (clone $query)->count(),
        ];
    }

    public function cashCollectedOrdersQuery(Driver $driver, ?Request $request = null): Builder
    {
        $query = Order::query()
            ->with(['customer', 'vendor', 'category'])
            ->where('driver_id', $driver->id)
            ->where('payment_method', 'cod')
            ->whereNotNull('cod_collected_at');

        $this->applyDateFilter($query, $request, 'cod_collected_at');

        return $query->orderByDesc('cod_collected_at');
    }

    public function recentDeliveriesQuery(Driver $driver, ?Request $request = null): Builder
    {
        $query = Order::query()
            ->with(['customer', 'vendor', 'category'])
            ->where(function (Builder $builder) use ($driver) {
                $builder->where('driver_id', $driver->id)
                    ->orWhereHas('orderItems', fn (Builder $items) => $items->where('driver_id', $driver->id));
            })
            ->where(function (Builder $builder) {
                $builder->whereIn('status', DriverDeliveryTab::activeDeliveryStatuses())
                    ->orWhereIn('status', ['delivered', 're_delivered'])
                    ->orWhere('status', 'cancelled')
                    ->orWhereHas('orderItems', function (Builder $items) {
                        $items->whereIn('status', [
                            ...DriverDeliveryTab::activeDeliveryStatuses(),
                            'delivered',
                            're_delivered',
                            'cancelled',
                        ]);
                    });
            });

        if ($request?->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('order_number', 'like', $term)
                    ->orWhere('item_title', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term));
            });
        }

        $this->applyDateFilter($query, $request, 'updated_at');

        return $query->orderByDesc('updated_at');
    }

    protected function applyDateFilter(Builder $query, ?Request $request, string $column = 'cod_collected_at'): void
    {
        if (! $request) {
            return;
        }

        if ($request->filled('from')) {
            $query->whereDate($column, '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate($column, '<=', $request->date('to'));
        }

        if ($request->filled('date')) {
            $query->whereDate($column, $request->date('date'));
        }
    }
}
