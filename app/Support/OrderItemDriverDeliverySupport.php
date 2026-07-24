<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderItem;
use InvalidArgumentException;

/**
 * Item-wise driver delivery progress.
 * Pickup / dispatch / deliver update only the targeted line item.
 */
class OrderItemDriverDeliverySupport
{
    public static function syncItem(
        OrderItem $item,
        Driver $driver,
        string $deliveryStatus,
        array $extra = []
    ): OrderItem {
        if ($item->driver_id !== null && (int) $item->driver_id !== (int) $driver->id) {
            throw new InvalidArgumentException('This item is assigned to another driver.');
        }

        if (! in_array($item->status, [
            OrderItem::STATUS_ACCEPTED,
            'in_progress',
            're_intransit',
            'delivered',
        ], true)) {
            throw new InvalidArgumentException(
                'Item "'.$item->title().'" is not ready for driver delivery (status: '.$item->status.').'
            );
        }

        $payload = array_merge([
            'driver_delivery_status' => $deliveryStatus,
            'driver_id' => $driver->id,
        ], $extra);

        if (blank($item->driver_assigned_at)) {
            $payload['driver_assigned_at'] = now();
        }

        // Pickup implies this item is at least In Transit (other items untouched).
        if (
            in_array($deliveryStatus, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            ], true)
            && in_array($item->status, [OrderItem::STATUS_ACCEPTED, OrderItem::STATUS_PENDING], true)
        ) {
            $payload['status'] = 'in_progress';
        }

        if ($deliveryStatus === Order::DRIVER_STATUS_PICKED_UP && empty($extra['driver_pickup_at'])) {
            $payload['driver_pickup_at'] = $item->driver_pickup_at ?: now();
        }

        if ($deliveryStatus === Order::DRIVER_STATUS_ACCEPTED) {
            $payload['driver_pickup_at'] = null;
        }

        $item->update($payload);

        return $item->fresh(['driver', 'order']);
    }

    /**
     * @deprecated Prefer syncItem() for item-wise flow.
     * Kept for legacy single-item / booking-level assign only when $onlyItemId is null
     * and no per-item drivers exist.
     */
    public static function syncForDriver(
        Order $order,
        Driver $driver,
        string $deliveryStatus,
        array $extra = [],
        ?int $onlyItemId = null
    ): int {
        $order->loadMissing('orderItems');

        if ($onlyItemId !== null) {
            $item = $order->orderItems->firstWhere('id', $onlyItemId);
            if (! $item) {
                throw new InvalidArgumentException('Item not found on this booking.');
            }
            self::syncItem($item, $driver, $deliveryStatus, $extra);

            return 1;
        }

        $items = $order->orderItems->where('driver_id', $driver->id);
        if ($items->isEmpty()) {
            $anyItemDriver = $order->orderItems->contains(fn (OrderItem $item) => $item->driver_id !== null);
            // Item-wise bookings: never touch unassigned / other items without explicit item_id.
            if ($anyItemDriver) {
                return 0;
            }

            // Legacy: whole booking assigned, no per-item drivers — only then update all active lines.
            $items = $order->orderItems->filter(
                fn (OrderItem $item) => $item->status !== OrderItem::STATUS_CANCELLED
                    && in_array($item->status, [
                        OrderItem::STATUS_ACCEPTED,
                        'in_progress',
                        're_intransit',
                    ], true)
            );
        }

        $updated = 0;
        foreach ($items as $item) {
            self::syncItem($item, $driver, $deliveryStatus, $extra);
            $updated++;
        }

        return $updated;
    }

    public static function markAssigned(OrderItem $item): void
    {
        if (! $item->driver_id) {
            $item->update([
                'driver_delivery_status' => null,
                'driver_pickup_at' => null,
                'driver_assigned_at' => null,
            ]);

            return;
        }

        $item->update([
            'driver_delivery_status' => Order::DRIVER_STATUS_ACCEPTED,
            'driver_assigned_at' => $item->driver_assigned_at ?: now(),
            'driver_pickup_at' => null,
        ]);
    }

    /**
     * Effective driver delivery status for an item.
     * Does NOT inherit booking-level pickup onto other items (item-wise isolation).
     */
    public static function effectiveDriverDeliveryStatus(OrderItem $item, ?Order $order = null): ?string
    {
        if (filled($item->driver_delivery_status)) {
            return $item->driver_delivery_status;
        }

        $order ??= $item->relationLoaded('order') ? $item->order : $item->order()->first();
        if (! $order || ! filled($order->driver_delivery_status)) {
            return null;
        }

        $order->loadMissing('orderItems');
        $anyItemDriver = $order->orderItems->contains(fn (OrderItem $row) => $row->driver_id !== null);

        // Item-wise assignment is active — never bleed booking status onto items.
        if ($anyItemDriver) {
            return null;
        }

        // Legacy booking-level only.
        if ($item->driver_id === null || (int) $item->driver_id === (int) $order->driver_id) {
            return $order->driver_delivery_status;
        }

        return null;
    }

    /**
     * Roll up booking driver_delivery_status from this driver's assigned items
     * without forcing every item to the same value.
     */
    public static function refreshBookingDriverStatusFromItems(Order $order, Driver $driver): void
    {
        $order->loadMissing('orderItems');
        $mine = $order->orderItems->where('driver_id', $driver->id);

        if ($mine->isEmpty()) {
            return;
        }

        $done = ['delivered', 'returned', 're_delivered', 'completed'];
        $active = $mine->where('status', '!=', OrderItem::STATUS_CANCELLED);

        // All of this driver's items delivered → mark booking driver leg complete.
        if ($active->isNotEmpty() && $active->every(fn (OrderItem $item) => in_array($item->status, $done, true))) {
            $order->forceFill([
                'driver_id' => $driver->id,
                'driver_delivery_status' => null,
                'driver_delivered_at' => $order->driver_delivered_at ?: now(),
                'driver_scheduled_for' => null,
                'driver_rescheduled_at' => null,
            ])->save();

            return;
        }

        $statuses = $mine
            ->filter(fn (OrderItem $item) => ! in_array($item->status, [...$done, OrderItem::STATUS_CANCELLED], true))
            ->pluck('driver_delivery_status')
            ->filter()
            ->values();

        if ($statuses->isEmpty()) {
            return;
        }

        // Booking-level mirrors the furthest progress among this driver's open items.
        $rank = [
            Order::DRIVER_STATUS_ACCEPTED => 1,
            Order::DRIVER_STATUS_RESCHEDULED => 2,
            Order::DRIVER_STATUS_PICKED_UP => 3,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 4,
        ];

        $furthest = $statuses->sortByDesc(fn ($s) => $rank[$s] ?? 0)->first();
        $pickupAt = $mine->first(fn (OrderItem $i) => $i->driver_pickup_at)?->driver_pickup_at;

        $order->forceFill([
            'driver_id' => $driver->id,
            'driver_delivery_status' => $furthest,
            'driver_assigned_at' => $order->driver_assigned_at ?: now(),
            'driver_delivered_at' => null,
            'driver_pickup_at' => $furthest === Order::DRIVER_STATUS_PICKED_UP
                || $furthest === Order::DRIVER_STATUS_OUT_FOR_DELIVERY
                ? ($order->driver_pickup_at ?: $pickupAt ?: now())
                : $order->driver_pickup_at,
        ])->save();
    }

    public static function resolveTargetItem(Order $order, Driver $driver, ?int $itemId): ?OrderItem
    {
        $order->loadMissing('orderItems');

        if ($itemId !== null) {
            $item = $order->orderItems->firstWhere('id', $itemId);
            if (! $item) {
                throw new InvalidArgumentException('Item not found on this booking.');
            }
            if ($item->driver_id !== null && (int) $item->driver_id !== (int) $driver->id) {
                throw new InvalidArgumentException('This item is assigned to another driver.');
            }

            return $item;
        }

        $mine = $order->orderItems->where('driver_id', $driver->id)->values();
        if ($mine->count() === 1) {
            return $mine->first();
        }

        // Legacy: no item drivers — single active line.
        $anyItemDriver = $order->orderItems->contains(fn (OrderItem $i) => $i->driver_id !== null);
        if (! $anyItemDriver) {
            $active = $order->orderItems->filter(
                fn (OrderItem $i) => in_array($i->status, [OrderItem::STATUS_ACCEPTED, 'in_progress', 're_intransit'], true)
            )->values();
            if ($active->count() === 1) {
                return $active->first();
            }
        }

        return null;
    }
}
