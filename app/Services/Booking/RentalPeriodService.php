<?php

namespace App\Services\Booking;

use App\Models\Order;
use Carbon\Carbon;

class RentalPeriodService
{
    /**
     * Start the rental clock on delivery day, preserving the paid duration.
     * Requested dates are only a schedule estimate until the outfit is delivered.
     */
    public function activateOnDelivery(Order $order): void
    {
        if (! $order->isRental()) {
            return;
        }

        $duration = $order->rentalDurationDays();
        if ($duration === null || $duration < 1) {
            $duration = 1;
        }

        $start = $this->deliveryDay($order);
        $end = $start->copy()->addDays($duration - 1);

        $order->forceFill([
            'rental_start_date' => $start->toDateString(),
            'rental_end_date' => $end->toDateString(),
            'return_due_date' => $end->copy()->addDay()->toDateString(),
        ])->saveQuietly();
    }

    protected function deliveryDay(Order $order): Carbon
    {
        $deliveredAt = $order->driver_delivered_at ?? now();

        return Carbon::parse($deliveredAt)->startOfDay();
    }
}
