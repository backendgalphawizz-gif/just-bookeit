<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Booking status graph aligned to the product architecture:
 *
 * NEW → ACCEPTED → IN TRANSIT (in_progress) → DELIVERED → RENTAL ACTIVE
 *   → RETURN IN TRANSIT (re_intransit) → RETURNED → COMPLETED
 *
 * Branches: NEW→cancelled (rejected), ACCEPTED→cancelled,
 * RENTAL ACTIVE / DELIVERED→rework (designer), rework→re_intransit→re_delivered→completed.
 */
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

        if (self::isDispatchStatus($order->status) && blank($order->delivery_otp)) {
            $order->delivery_otp = Order::generateDeliveryOtpValue();
        }
    }

    /**
     * Reset driver assignment when a new delivery/return leg starts
     * so admin can assign (or driver can claim) again.
     */
    public static function resetDriverAssignment(Order $order): void
    {
        $order->driver_id = null;
        $order->driver_delivery_status = null;
        $order->driver_assigned_at = null;
        $order->driver_pickup_at = null;
        $order->driver_delivered_at = null;
        $order->driver_scheduled_for = null;
        $order->driver_rescheduled_at = null;
        $order->driver_rejection_reason = null;
        $order->delivery_otp = Order::generateDeliveryOtpValue();
    }

    /**
     * Clear per-item driver so admin can assign a new driver for the return leg.
     */
    public static function resetItemDriverAssignment(OrderItem $item): void
    {
        $item->forceFill([
            'driver_id' => null,
            'driver_assigned_at' => null,
            'driver_delivery_status' => null,
            'driver_pickup_at' => null,
        ])->save();
    }

    public static function isReturnLegItem(OrderItem $item): bool
    {
        if ($item->status === 're_intransit') {
            return true;
        }

        // Premature "returned" before return pickup — still the return leg.
        return $item->status === 'returned' && blank($item->driver_pickup_at);
    }

    /**
     * True when the driver's visible dispatch items are all on the return leg.
     * Mixed outbound + return assignments stay outbound at booking level;
     * each line item still uses its own addresses via isReturnLegItem().
     *
     * @param  Collection<int, OrderItem>|iterable<int, OrderItem>  $items
     */
    public static function isReturnLegForItems(iterable $items): bool
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        if ($collection->isEmpty()) {
            return false;
        }

        $dispatch = $collection->filter(
            fn (OrderItem $item) => in_array($item->status, ['in_progress', 're_intransit'], true)
                || self::isReturnLegItem($item)
        );

        if ($dispatch->isEmpty()) {
            return false;
        }

        return $dispatch->every(fn (OrderItem $item) => self::isReturnLegItem($item));
    }

    /**
     * Pickup / delivery addresses for the current leg.
     * Outbound: vendor → customer. Return: customer → vendor (swapped).
     *
     * @return array{
     *     pickup_address: ?string,
     *     delivery_address: ?string,
     *     is_return_leg: bool,
     *     leg: 'outbound'|'return'
     * }
     */
    public static function addressesForLeg(Order $order, bool $isReturnLeg): array
    {
        $order->loadMissing('vendor');

        $vendorAddress = null;
        if ($order->vendor) {
            $vendorAddress = CustomerApiPresenter::vendorAddress($order->vendor)['full_address']
                ?? $order->vendor->address;
        }
        if (blank($vendorAddress)) {
            $vendorAddress = $order->pickup_address;
        }

        $customerAddress = $order->delivery_address;

        if ($isReturnLeg) {
            return [
                'pickup_address' => $customerAddress,
                'delivery_address' => $vendorAddress,
                'is_return_leg' => true,
                'leg' => 'return',
            ];
        }

        return [
            'pickup_address' => filled($order->pickup_address) ? $order->pickup_address : $vendorAddress,
            'delivery_address' => $customerAddress,
            'is_return_leg' => false,
            'leg' => 'outbound',
        ];
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
            // Rentals: delivery completes into rental_active.
            'in_progress' => $isRental
                ? ['delivered', 'rental_active', 'cancelled']
                : ['delivered', 'cancelled'],
            // After delivery: rentals enter rental_active; designers may rework or complete.
            'delivered' => $isRental
                ? ['rental_active', 're_intransit', 'cancelled']
                : ['rework', 'completed', 'cancelled'],
            'rental_active' => ['re_intransit', 'rework', 'cancelled'],
            'rework' => ['re_intransit', 'cancelled'],
            're_intransit' => $isRental
                ? ['returned', 'cancelled']
                : ['re_delivered', 'cancelled'],
            'returned' => ['completed', 're_intransit'],
            're_delivered' => ['completed', 'rework'],
            'completed', 'cancelled', 'refunded' => [],
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

    /**
     * Apply a validated status transition with side effects (OTP, driver reset).
     */
    public static function applyTransition(Order $order, string $nextStatus): Order
    {
        if ($nextStatus === 'delivered' && $order->isRental()) {
            $nextStatus = 'rental_active';
        }

        if (! self::canTransitionTo($order, $nextStatus)) {
            throw new InvalidArgumentException(
                'Invalid status transition from '.$order->status.' to '.$nextStatus.'.'
            );
        }

        $previous = $order->status;
        $order->status = $nextStatus;

        if ($nextStatus === 're_intransit' && $previous !== 're_intransit') {
            self::resetDriverAssignment($order);
        } elseif ($nextStatus === 'rework' && $previous !== 'rework') {
            // Rework stays with the vendor — clear any outbound driver until vendor dispatches return.
            self::resetDriverAssignment($order);
        } elseif ($nextStatus === 'in_progress') {
            self::prepareForTransit($order);
        }

        $order->save();

        return $order->fresh();
    }
}
