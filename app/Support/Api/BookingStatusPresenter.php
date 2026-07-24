<?php

namespace App\Support\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderItemDriverDeliverySupport;

/**
 * Shared booking/item status fields so customer (v1), vendor (v2), and driver (v3)
 * APIs all show the same picked_up / out_for_delivery progress.
 */
class BookingStatusPresenter
{
    /**
     * @return array{
     *   status: string,
     *   status_raw: string,
     *   status_label: string,
     *   is_picked_up: bool,
     *   driver_delivery_status: ?string,
     *   driver_delivery_status_label: ?string
     * }
     */
    public static function forItem(OrderItem $item, ?Order $order = null): array
    {
        $order ??= $item->relationLoaded('order') ? $item->order : $item->order()->first();
        if ($order) {
            $item->setRelation('order', $order);
        }

        $driverDelivery = OrderItemDriverDeliverySupport::effectiveDriverDeliveryStatus($item, $order);
        $apiStatus = VendorBookingStatus::toApi($item->status);

        if (in_array($item->status, ['accepted', 'in_progress', 're_intransit'], true)) {
            $apiStatus = match ($driverDelivery) {
                Order::DRIVER_STATUS_PICKED_UP => 'picked_up',
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'out_for_delivery',
                Order::DRIVER_STATUS_RESCHEDULED => 'rescheduled',
                default => $apiStatus,
            };
        }

        return [
            'status' => $apiStatus,
            'status_raw' => $item->status,
            'status_label' => $item->statusLabel(),
            'is_picked_up' => $item->isPickedUp(),
            'driver_delivery_status' => $driverDelivery,
            'driver_delivery_status_label' => $item->driverDeliveryStatusLabel(),
        ];
    }

    /**
     * Booking lifecycle status stays unchanged when a driver picks up items.
     * Pickup progress is exposed only via driver_delivery_status / is_picked_up.
     *
     * @return array{
     *   status: string,
     *   status_raw: string,
     *   status_label: string,
     *   is_picked_up: bool,
     *   driver_delivery_status: ?string
     * }
     */
    public static function forBooking(Order $order, bool $vendorAliases = false): array
    {
        $order->loadMissing('orderItems');

        $driverStatus = $order->driver_delivery_status;
        $itemDriverStatuses = $order->orderItems
            ->map(fn (OrderItem $item) => OrderItemDriverDeliverySupport::effectiveDriverDeliveryStatus($item, $order))
            ->filter()
            ->values();

        if ($itemDriverStatuses->contains(Order::DRIVER_STATUS_OUT_FOR_DELIVERY) || $driverStatus === Order::DRIVER_STATUS_OUT_FOR_DELIVERY) {
            $driverStatus = Order::DRIVER_STATUS_OUT_FOR_DELIVERY;
        } elseif ($itemDriverStatuses->contains(Order::DRIVER_STATUS_PICKED_UP) || $driverStatus === Order::DRIVER_STATUS_PICKED_UP) {
            $driverStatus = Order::DRIVER_STATUS_PICKED_UP;
        } elseif ($itemDriverStatuses->contains(Order::DRIVER_STATUS_RESCHEDULED) || $driverStatus === Order::DRIVER_STATUS_RESCHEDULED) {
            $driverStatus = Order::DRIVER_STATUS_RESCHEDULED;
        }

        return [
            'status' => $vendorAliases
                ? VendorBookingStatus::toApi($order->status)
                : $order->status,
            'status_raw' => $order->status,
            'status_label' => $order->statusLabel(),
            'is_picked_up' => in_array($driverStatus, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            ], true),
            'driver_delivery_status' => $driverStatus,
        ];
    }
}
