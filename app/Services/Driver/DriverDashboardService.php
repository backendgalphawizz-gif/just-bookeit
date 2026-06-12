<?php

namespace App\Services\Driver;

use App\Models\Driver;
use App\Models\Order;

class DriverDashboardService
{
    public function stats(Driver $driver): array
    {
        $base = Order::query()->where('driver_id', $driver->id);

        return [
            'total_earnings' => round((float) $driver->total_earnings, 2),
            'wallet_balance' => round((float) $driver->wallet_balance, 2),
            'assigned_deliveries' => (clone $base)
                ->where('status', 'in_transit')
                ->count(),
            'pending_deliveries' => Order::query()
                ->where('status', 'in_transit')
                ->whereNull('driver_id')
                ->count(),
            'completed_deliveries' => (clone $base)
                ->where('status', 'delivered')
                ->count(),
            'cancelled_deliveries' => (clone $base)
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Order> */
    public function newDeliveries(int $limit = 5)
    {
        return Order::query()
            ->with(['customer', 'vendor', 'category'])
            ->where('status', 'in_transit')
            ->whereNull('driver_id')
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }
}
