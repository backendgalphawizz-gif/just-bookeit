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

        if (in_array($order->status, ['in_progress', 're_intransit'], true) && blank($order->delivery_otp)) {
            $order->delivery_otp = Order::generateDeliveryOtpValue();
        }
    }

    public static function isDispatchStatus(string $status): bool
    {
        return in_array($status, ['in_progress', 're_intransit'], true);
    }

    /** @return list<string> */
    public static function allowedNextStatuses(Order|string $orderOrStatus): array
    {
        if ($orderOrStatus instanceof Order) {
            return self::allowedNextStatusesForOrder($orderOrStatus);
        }

        return self::allowedNextStatusesForCurrent($orderOrStatus, isRental: false);
    }

    /** @return list<string> */
    protected static function allowedNextStatusesForOrder(Order $order): array
    {
        return self::allowedNextStatusesForCurrent($order->status, $order->isRental());
    }

    /** @return list<string> */
    protected static function allowedNextStatusesForCurrent(string $current, bool $isRental): array
    {
        return match ($current) {
            'new' => ['pending_acceptance', 'accepted', 'cancelled'],
            'pending_acceptance' => ['accepted', 'cancelled'],
            'accepted' => ['in_progress', 'cancelled'],
            'in_progress' => ['delivered', 'cancelled'],
            'delivered' => $isRental
                ? ['returned', 're_intransit', 'cancelled']
                : ['returned', 'rework', 'cancelled'],
            'returned' => [],
            'rework' => ['re_intransit', 'cancelled'],
            're_intransit' => $isRental
                ? ['returned', 'cancelled']
                : ['re_delivered', 'cancelled'],
            're_delivered' => [],
            default => [],
        };
    }

    public static function canTransitionTo(Order|string $orderOrCurrent, ?string $next = null): bool
    {
        if ($orderOrCurrent instanceof Order) {
            $order = $orderOrCurrent;
            $current = $order->status;
        } else {
            $current = $orderOrCurrent;
            $order = null;
        }

        if ($next === null) {
            return false;
        }

        if ($current === $next) {
            return true;
        }

        $allowed = $order instanceof Order
            ? self::allowedNextStatusesForOrder($order)
            : self::allowedNextStatusesForCurrent($current, isRental: false);

        return in_array($next, $allowed, true);
    }
}
