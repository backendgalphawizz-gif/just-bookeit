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

        if ($order->status === 'in_progress' && blank($order->delivery_otp)) {
            $order->delivery_otp = Order::generateDeliveryOtpValue();
        }
    }

    public static function isDispatchStatus(string $status): bool
    {
        return in_array($status, ['in_progress', 're_intransit'], true);
    }

    /** @return list<string> */
    public static function allowedNextStatuses(string $current): array
    {
        return match ($current) {
            'pending_acceptance' => ['accepted', 'cancelled'],
            'accepted' => ['in_progress', 'cancelled'],
            'in_progress' => ['delivered', 'cancelled'],
            'delivered' => ['returned', 'rework', 'cancelled'],
            'returned' => [],
            'rework' => ['re_intransit', 'cancelled'],
            're_intransit' => ['re_delivered', 'cancelled'],
            're_delivered' => [],
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
