<?php

namespace App\Services\Vendor;

use App\Models\Order;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VendorDashboardService
{
    public function stats(Vendor $vendor): array
    {
        $base = Order::query()->where('vendor_id', $vendor->id);
        $today = now()->startOfDay();
        $ytdStart = now()->startOfYear();
        $monthStart = now()->startOfMonth();

        return [
            'total_orders_today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'total_orders_ytd' => (clone $base)->where('created_at', '>=', $ytdStart)->count(),
            'completed_today' => (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $today)->count(),
            'completed_ytd' => (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $ytdStart)->count(),
            'new_today' => (clone $base)->whereIn('status', ['new', 'pending_acceptance'])->where('created_at', '>=', $today)->count(),
            'in_progress_today' => (clone $base)->whereIn('status', Order::IN_PROGRESS_STATUSES)->where('updated_at', '>=', $today)->count(),
            'earnings_month' => (float) (clone $base)->where('payment_status', 'success')->where('created_at', '>=', $monthStart)->sum('amount'),
            'earnings_ytd' => (float) (clone $base)->where('payment_status', 'success')->where('created_at', '>=', $ytdStart)->sum('amount'),
        ];
    }

    /** @return Collection<int, Order> */
    public function deliverySchedule(Vendor $vendor, ?Carbon $date = null): Collection
    {
        $date = ($date ?? now())->startOfDay();

        return Order::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['accepted', 'in_progress', 'in_transit', 'delivered'])
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
            ->with(['customer', 'category', 'driver'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
