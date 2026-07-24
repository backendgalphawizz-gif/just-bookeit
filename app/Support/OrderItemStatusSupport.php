<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Item-level status graph + booking rollup.
 *
 * Booking status depends on line items:
 * - all cancelled → cancelled
 * - all active completed → completed
 * - otherwise pending (fulfillment), booking.status = slowest active item
 */
class OrderItemStatusSupport
{
    /** Lifecycle ranks used to pick the slowest active item. */
    public const STATUS_RANK = [
        'pending_acceptance' => 10,
        'accepted' => 20,
        'in_progress' => 30,
        'delivered' => 40,
        'rental_active' => 50,
        'rework' => 55,
        're_intransit' => 60,
        'returned' => 70,
        're_delivered' => 70,
        'completed' => 80,
        'cancelled' => 0,
        'refunded' => 0,
    ];

    public static function isRentalItem(OrderItem $item, ?Order $order = null): bool
    {
        $slug = $item->categorySlug();
        if ($slug === 'fashion-designer') {
            return false;
        }
        if (in_array($slug, ['rented-dress', 'rented-jewellery'], true)) {
            return true;
        }

        $order ??= $item->order;

        return $order ? $order->isRental() : true;
    }

    /**
     * Status after outbound driver/vendor delivery.
     * Rental dress/jewellery → rental_active; fashion designer stays delivered.
     */
    public static function statusAfterOutboundDelivery(OrderItem $item, ?Order $order = null): string
    {
        return self::isRentalItem($item, $order) ? 'rental_active' : 'delivered';
    }

    /** @return list<string> */
    public static function allowedNextStatuses(OrderItem $item, ?Order $order = null): array
    {
        $isRental = self::isRentalItem($item, $order);

        return match ($item->status) {
            OrderItem::STATUS_PENDING => [OrderItem::STATUS_ACCEPTED, OrderItem::STATUS_CANCELLED],
            OrderItem::STATUS_ACCEPTED => ['in_progress', OrderItem::STATUS_CANCELLED],
            // Rentals: delivery completes into rental_active (Delivered stays a done tracking step).
            'in_progress' => $isRental
                ? ['delivered', 'rental_active', OrderItem::STATUS_CANCELLED]
                : ['delivered', OrderItem::STATUS_CANCELLED],
            'delivered' => $isRental
                // Start return pickup (re_intransit); admin assigns driver. Final returned only after pickup.
                ? ['rental_active', 're_intransit', OrderItem::STATUS_CANCELLED]
                : ['rework', 'completed', OrderItem::STATUS_CANCELLED],
            'rental_active' => ['re_intransit', 'rework', OrderItem::STATUS_CANCELLED],
            'rework' => ['re_intransit', OrderItem::STATUS_CANCELLED],
            're_intransit' => $isRental
                ? ['returned', OrderItem::STATUS_CANCELLED]
                : ['re_delivered', OrderItem::STATUS_CANCELLED],
            // Allow reopening return pickup if marked returned too early (admin can reassign driver).
            'returned' => ['completed', 're_intransit'],
            're_delivered' => ['completed', 'rework'],
            'completed', OrderItem::STATUS_CANCELLED => [],
            default => [],
        };
    }

    public static function canTransitionTo(OrderItem $item, string $next, ?Order $order = null): bool
    {
        if ($item->status === $next) {
            return true;
        }

        // `returned` = rented dress/jewellery is back with the vendor — never for designer items.
        if ($next === 'returned' && ! self::isRentalItem($item, $order)) {
            return false;
        }

        return in_array($next, self::allowedNextStatuses($item, $order), true);
    }

    public static function assertCanTransition(OrderItem $item, string $next, ?Order $order = null): void
    {
        if ($next === 'returned' && ! self::isRentalItem($item, $order)) {
            throw new InvalidArgumentException(
                'Status "returned" is only for rented dress/jewellery product return to the vendor.'
            );
        }

        if (! self::canTransitionTo($item, $next, $order)) {
            throw new InvalidArgumentException(
                'Invalid item status transition from '.$item->status.' to '.$next.'.'
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return array{status: string, fulfillment_state: string, is_pending: bool}
     */
    public static function resolveBookingFromItems(Collection $items, Order $booking): array
    {
        if ($items->isEmpty()) {
            $terminal = in_array($booking->status, ['completed', 'cancelled', 'refunded'], true);

            return [
                'status' => $booking->status,
                'fulfillment_state' => match ($booking->status) {
                    'completed' => 'completed',
                    'cancelled', 'refunded' => 'cancelled',
                    default => 'pending',
                },
                'is_pending' => ! $terminal,
            ];
        }

        $allCancelled = $items->every(fn (OrderItem $item) => $item->status === OrderItem::STATUS_CANCELLED);
        if ($allCancelled) {
            return [
                'status' => 'cancelled',
                'fulfillment_state' => 'cancelled',
                'is_pending' => false,
            ];
        }

        $active = $items->where('status', '!=', OrderItem::STATUS_CANCELLED)->values();

        $allCompleted = $active->every(fn (OrderItem $item) => $item->status === 'completed');
        if ($allCompleted) {
            return [
                'status' => 'completed',
                'fulfillment_state' => 'completed',
                'is_pending' => false,
            ];
        }

        // Item-wise accept/reject: any undecided line keeps the booking pending.
        // Booking becomes accepted only when every active item is accepted (or further).
        if ($active->contains(fn (OrderItem $item) => $item->status === OrderItem::STATUS_PENDING)) {
            return [
                'status' => 'pending_acceptance',
                'fulfillment_state' => 'pending',
                'is_pending' => true,
            ];
        }

        // All active items decided and still in acceptance stage → accepted.
        if ($active->every(fn (OrderItem $item) => $item->status === OrderItem::STATUS_ACCEPTED)) {
            return [
                'status' => 'accepted',
                'fulfillment_state' => 'pending',
                'is_pending' => true,
            ];
        }

        $unique = $active->pluck('status')->unique()->values();
        if ($unique->count() === 1) {
            return [
                'status' => (string) $unique->first(),
                'fulfillment_state' => 'pending',
                'is_pending' => true,
            ];
        }

        // Mixed fulfillment progress → booking stays at slowest active status.
        return [
            'status' => self::slowestStatus($active),
            'fulfillment_state' => 'pending',
            'is_pending' => true,
        ];
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    public static function slowestStatus(Collection $items): string
    {
        $slowest = null;
        $slowestRank = PHP_INT_MAX;

        foreach ($items as $item) {
            $rank = self::STATUS_RANK[$item->status] ?? 15;
            if ($rank < $slowestRank) {
                $slowestRank = $rank;
                $slowest = $item->status;
            }
        }

        return $slowest ?: 'pending_acceptance';
    }

    /**
     * Apply the same fulfillment status to every active (non-cancelled) item.
     * When $driver is set, only that driver's assigned items are updated (item-wise dispatch).
     *
     * @return int Number of items updated
     */
    public static function applyStatusToActiveItems(
        Order $booking,
        string $nextStatus,
        bool $force = false,
        ?\App\Models\Driver $driver = null
    ): int {
        $booking->loadMissing('orderItems');
        $updated = 0;

        $items = $booking->orderItems;
        if ($driver) {
            $driverItems = $items->where('driver_id', $driver->id);
            // Item-wise assign: only touch this driver's lines. If none, fall back to all active
            // only when the booking has no per-item drivers at all (legacy).
            if ($driverItems->isNotEmpty()) {
                $items = $driverItems;
            } elseif ($items->contains(fn (OrderItem $item) => $item->driver_id !== null)) {
                return 0;
            }
        }

        foreach ($items as $item) {
            if ($item->status === OrderItem::STATUS_CANCELLED) {
                continue;
            }

            $itemNext = $nextStatus;
            // Per-item final status for return vs outbound legs.
            if ($driver && $item->status === 're_intransit') {
                $itemNext = self::isRentalItem($item, $booking) ? 'returned' : 're_delivered';
            } elseif ($driver && $item->status === 'in_progress') {
                $itemNext = self::statusAfterOutboundDelivery($item, $booking);
            } elseif ($itemNext === 'delivered') {
                // Booking/vendor "delivered" on a rental line → rental active immediately.
                $itemNext = self::statusAfterOutboundDelivery($item, $booking);
            }

            if ($item->status === $itemNext) {
                continue;
            }

            if (! $force) {
                self::assertCanTransition($item, $itemNext, $booking);
            } elseif (! in_array($itemNext, OrderItem::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid item status: '.$itemNext);
            }

            $previousItemStatus = $item->status;
            $payload = ['status' => $itemNext];
            if ($itemNext === OrderItem::STATUS_CANCELLED && blank($item->cancellation_reason)) {
                $payload['cancellation_reason'] = $booking->cancellation_reason ?: 'Cancelled with booking';
                $payload['responded_at'] = now();
            }
            if (in_array($itemNext, [OrderItem::STATUS_ACCEPTED, OrderItem::STATUS_CANCELLED], true)) {
                $payload['responded_at'] = $item->responded_at ?? now();
            }

            $item->update($payload);

            if (
                in_array($itemNext, ['rework', 're_intransit'], true)
                && $previousItemStatus !== $itemNext
            ) {
                OrderDispatchSupport::resetItemDriverAssignment($item->fresh());
            }

            $updated++;
        }

        return $updated;
    }
}
