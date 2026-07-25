<?php

namespace App\Support;

/**
 * Shared status badge classes for the customer website (aligned with API status set).
 */
class WebBookingStatus
{
    public static function badgeClass(?string $status): string
    {
        return match ($status) {
            'new', 'pending_acceptance' => 'new',
            'accepted' => 'accepted',
            'in_progress', 'processing', 'partially_delivered' => 'in_progress',
            'delivered' => 'delivered',
            'rental_active' => 'rental_active',
            'rework' => 'rework',
            're_intransit' => 're_intransit',
            'returned' => 'returned',
            're_delivered' => 're_delivered',
            'completed' => 'completed',
            'cancelled', 'refunded', 'partially_cancelled' => 'cancelled',
            default => 'default',
        };
    }

    public static function historyBadgeClass(?string $status): string
    {
        return match (self::badgeClass($status)) {
            'new', 'accepted' => 'pending',
            'in_progress', 'rework', 're_intransit' => 'in_progress',
            'delivered', 'rental_active', 'returned', 're_delivered', 'completed' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'default',
        };
    }

    public static function itemCanRequestProductReturn(\App\Models\OrderItem $item, ?\App\Models\Order $order = null): bool
    {
        if (! OrderItemStatusSupport::isRentalItem($item, $order)) {
            return false;
        }

        return in_array($item->status, ['delivered', 'rental_active'], true);
    }

    public static function bookingCanRequestProductReturn(\App\Models\Order $order): bool
    {
        $order->loadMissing('orderItems');

        if ($order->orderItems->isNotEmpty()) {
            return $order->orderItems->contains(
                fn ($item) => self::itemCanRequestProductReturn($item, $order)
            );
        }

        return in_array($order->status, ['rental_active', 'delivered'], true) && $order->isRental();
    }

    public static function bookingCanReview(\App\Models\Order $order): bool
    {
        $order->loadMissing(['orderItems.review', 'reviews']);

        if ($order->orderItems->isNotEmpty()) {
            return $order->orderItems->contains(
                fn ($item) => in_array($item->status, \App\Models\OrderReview::reviewableStatuses(), true) && ! $item->review
            );
        }

        $hasBookingLevel = $order->reviews->contains(fn ($review) => blank($review->order_item_id));

        return in_array($order->status, \App\Models\OrderReview::reviewableStatuses(), true) && ! $hasBookingLevel;
    }
}
