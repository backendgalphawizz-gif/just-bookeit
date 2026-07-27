<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class OrderItemStatusSupport
{
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
            'in_progress' => $isRental
                ? ['delivered', 'rental_active', OrderItem::STATUS_CANCELLED]
                : ['delivered', OrderItem::STATUS_CANCELLED],
            'delivered' => $isRental
                ? ['rental_active', 're_intransit', OrderItem::STATUS_CANCELLED]
                : ['rework', 'completed', OrderItem::STATUS_CANCELLED],
            'rental_active' => ['re_intransit', 'rework', OrderItem::STATUS_CANCELLED],
            'rework' => ['re_intransit', OrderItem::STATUS_CANCELLED],
            're_intransit' => $isRental
                ? ['returned', OrderItem::STATUS_CANCELLED]
                : ['completed', 're_delivered', OrderItem::STATUS_CANCELLED],
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

        if ($next === 'returned' && ! self::isRentalItem($item, $order)) {
            return false;
        }

        return in_array($next, self::allowedNextStatuses($item, $order), true);
    }

    /**
     * Vendor "returned" on an active rental starts Return In Transit (admin assigns driver).
     * Final returned is only from re_intransit after pickup.
     */
    public static function normalizeVendorRequestedStatus(OrderItem $item, string $nextStatus, ?Order $order = null): string
    {
        if (
            $nextStatus === 'returned'
            && self::isRentalItem($item, $order)
            && in_array($item->status, ['delivered', 'rental_active'], true)
        ) {
            return 're_intransit';
        }

        return $nextStatus;
    }

    /** @return list<string> */
    public static function vendorActionStatuses(OrderItem $item, ?Order $order = null): array
    {
        return array_values(array_filter(
            self::allowedNextStatuses($item, $order),
            fn (string $status) => $status !== OrderItem::STATUS_CANCELLED
        ));
    }

    public static function vendorActionLabel(string $status, OrderItem $item, ?Order $order = null): string
    {
        return match ($status) {
            'in_progress' => 'Mark In Transit',
            'delivered' => 'Mark Delivered',
            'rental_active' => 'Mark Rental Active',
            're_intransit' => $item->status === 'rework'
                ? 'Dispatch Rework (Return In Transit)'
                : 'Start Return Pickup',
            'returned' => 'Mark Returned to Vendor',
            'rework' => 'Send for Rework',
            're_delivered' => 'Mark Re-delivered',
            'completed' => 'Mark Completed',
            default => OrderItem::STATUS_LABELS[$status]
                ?? ucfirst(str_replace('_', ' ', $status)),
        };
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

        if ($active->contains(fn (OrderItem $item) => $item->status === OrderItem::STATUS_PENDING)) {
            return [
                'status' => 'pending_acceptance',
                'fulfillment_state' => 'pending',
                'is_pending' => true,
            ];
        }

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
            if ($driver && $item->status === 're_intransit') {
                $itemNext = self::isRentalItem($item, $booking) ? 'returned' : 're_delivered';
            } elseif ($driver && $item->status === 'in_progress') {
                $itemNext = self::statusAfterOutboundDelivery($item, $booking);
            } elseif ($itemNext === 'delivered') {
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
            if (in_array($itemNext, ['delivered', 'rental_active'], true) && blank($item->delivered_at)) {
                $payload['delivered_at'] = now();
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
