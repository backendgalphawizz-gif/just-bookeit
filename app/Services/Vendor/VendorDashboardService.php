<?php

namespace App\Services\Vendor;

use App\Models\Order;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VendorDashboardService
{
    public function stats(Vendor $vendor, ?Carbon $earningsMonth = null): array
    {
        $base = Order::query()->where('vendor_id', $vendor->id)->paymentConfirmed();
        $today = now()->startOfDay();
        $ytdStart = now()->startOfYear();
        $month = ($earningsMonth ?? now())->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        return [
            'total_orders_today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'total_orders_ytd' => (clone $base)->where('created_at', '>=', $ytdStart)->count(),
            'completed_today' => (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $today)->count(),
            'completed_ytd' => (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $ytdStart)->count(),
            'new_today' => (clone $base)->whereIn('status', ['new', 'pending_acceptance'])->where('created_at', '>=', $today)->count(),
            'in_progress_today' => (clone $base)->whereIn('status', Order::IN_PROGRESS_STATUSES)->where('updated_at', '>=', $today)->count(),
            'earnings_month' => (float) (clone $base)
                ->where('payment_status', 'success')
                ->whereBetween('created_at', [$month, $monthEnd])
                ->sum('amount'),
            'earnings_ytd' => (float) (clone $base)->where('payment_status', 'success')->where('created_at', '>=', $ytdStart)->sum('amount'),
        ];
    }

    /** @return Collection<int, Order> */
    public function deliverySchedule(Vendor $vendor, ?Carbon $date = null): Collection
    {
        $date = ($date ?? now())->startOfDay();

        return Order::query()
            ->where('vendor_id', $vendor->id)
            ->paymentConfirmed()
            ->whereIn('status', ['accepted', 'in_progress', 'delivered'])
            ->where(function ($q) use ($date) {
                $q->whereDate('rental_start_date', $date)
                    ->orWhereDate('rental_end_date', $date)
                    ->orWhereDate('updated_at', $date);
            })
            ->with(['customer', 'category'])
            ->orderBy('rental_start_date')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Order> */
    public function recentBookings(Vendor $vendor, int $limit = 8): Collection
    {
        return Order::query()
            ->where('vendor_id', $vendor->id)
            ->paymentConfirmed()
            ->with(['customer', 'category', 'driver'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
