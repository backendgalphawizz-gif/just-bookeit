<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonInterface;

/**
 * Fashion designer post-delivery rules:
 * - Customer may request rework within 48 hours of delivery.
 * - If no rework in 48 hours, item auto-completes.
 * - Rework path: rework → re_intransit → completed (re_delivered still allowed).
 */
class FashionDesignerLifecycleSupport
{
    public const REWORK_WINDOW_HOURS = 48;

    /**
     * True for non-rental (fashion designer) items that use the designer delivery track.
     */
    public static function usesDesignerDeliveryTrack(OrderItem $item, ?Order $order = null): bool
    {
        return ! OrderItemStatusSupport::isRentalItem($item, $order);
    }

    public static function deliveredAt(OrderItem $item, ?Order $order = null): ?CarbonInterface
    {
        if ($item->delivered_at) {
            return $item->delivered_at;
        }

        $order ??= $item->relationLoaded('order') ? $item->order : $item->order()->first();

        return $order?->driver_delivered_at;
    }

    public static function reworkDeadline(OrderItem $item, ?Order $order = null): ?CarbonInterface
    {
        $deliveredAt = self::deliveredAt($item, $order);

        return $deliveredAt?->copy()->addHours(self::REWORK_WINDOW_HOURS);
    }

    public static function reworkWindowOpen(OrderItem $item, ?Order $order = null): bool
    {
        if (! self::usesDesignerDeliveryTrack($item, $order)) {
            return true;
        }

        if ($item->status !== 'delivered' && $item->status !== 're_delivered') {
            return false;
        }

        $deadline = self::reworkDeadline($item, $order);
        if (! $deadline) {
            // No delivery timestamp yet — allow rework while still delivered.
            return in_array($item->status, ['delivered', 're_delivered'], true);
        }

        return now()->lte($deadline);
    }

    public static function canCustomerRequestRework(OrderItem $item, ?Order $order = null): bool
    {
        $order ??= $item->relationLoaded('order') ? $item->order : $item->order()->first();

        if (self::usesDesignerDeliveryTrack($item, $order)) {
            return in_array($item->status, ['delivered', 're_delivered'], true)
                && self::reworkWindowOpen($item, $order);
        }

        // Rentals: rework allowed while delivered / rental active / re-delivered (no 48h auto-complete).
        return in_array($item->status, ['delivered', 'rental_active', 're_delivered'], true);
    }

    public static function shouldAutoComplete(OrderItem $item, ?Order $order = null): bool
    {
        if (! self::usesDesignerDeliveryTrack($item, $order)) {
            return false;
        }

        if ($item->status !== 'delivered') {
            return false;
        }

        $deadline = self::reworkDeadline($item, $order);
        if (! $deadline) {
            // Fallback: use updated_at when status is delivered and no delivered_at.
            return $item->updated_at !== null
                && $item->updated_at->lte(now()->subHours(self::REWORK_WINDOW_HOURS));
        }

        return now()->greaterThan($deadline);
    }

    /** @return array<string, mixed> */
    public static function apiFields(OrderItem $item, ?Order $order = null): array
    {
        if (! self::usesDesignerDeliveryTrack($item, $order)) {
            return [
                'can_request_rework' => self::canCustomerRequestRework($item, $order),
                'rework_window_hours' => null,
                'rework_deadline_at' => null,
                'rework_deadline_label' => null,
                'rework_window_open' => null,
                'auto_complete_after_rework_window' => false,
            ];
        }

        $deadline = self::reworkDeadline($item, $order);
        $open = self::reworkWindowOpen($item, $order);

        return [
            'can_request_rework' => self::canCustomerRequestRework($item, $order),
            'rework_window_hours' => self::REWORK_WINDOW_HOURS,
            'rework_deadline_at' => $deadline?->toIso8601String(),
            'rework_deadline_label' => $deadline?->format('d M Y, g:i A'),
            'rework_window_open' => $item->status === 'delivered' ? $open : null,
            'auto_complete_after_rework_window' => true,
        ];
    }
}
