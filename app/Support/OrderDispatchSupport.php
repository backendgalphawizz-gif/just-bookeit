<?php

namespace App\Support;

use App\Models\Order;
use App\Support\Api\CustomerApiPresenter;

class OrderDispatchSupport
{
    public static function preparePickupAddress(Order $order): void
    {
        if (filled($order->pickup_address)) {
            return;
        }

        $order->loadMissing('vendor');

        if (! $order->vendor) {
            return;
        }

        $order->pickup_address = CustomerApiPresenter::vendorAddress($order->vendor)['full_address'] ?? $order->vendor->address;
    }

    public static function prepareForTransit(Order $order): void
    {
        self::preparePickupAddress($order);

        if ($order->status === 'in_transit' && blank($order->delivery_otp)) {
            $order->delivery_otp = Order::generateDeliveryOtpValue();
        }
    }

    /** @return list<string> */
    public static function allowedNextStatuses(string $current): array
    {
        return match ($current) {
            'pending_acceptance' => ['accepted', 'cancelled'],
            'accepted' => ['in_progress', 'cancelled'],
            'in_progress' => ['in_transit', 'cancelled'],
            'in_transit' => ['delivered'],
            default => [],
        };
    }

    public static function canTransitionTo(string $current, string $next): bool
    {
        if ($current === $next) {
            return true;
        }

        return in_array($next, self::allowedNextStatuses($current), true);
    }
}
