<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AppPushNotificationService;
use App\Services\Booking\RentalPeriodService;
use App\Services\Checkout\CheckoutRollupService;
use App\Services\Refund\RefundRequestService;

class OrderObserver
{
    public function created(Order $order): void
    {
        app(AppPushNotificationService::class)->orderCreated($order);
    }

    public function updated(Order $order): void
    {
        $notifications = app(AppPushNotificationService::class);

        if ($order->wasChanged('payment_status') && $order->payment_status === 'success') {
            $notifications->orderPaymentSucceeded($order);
        }

        if ($order->wasChanged('status')) {
            $notifications->orderStatusChanged($order, (string) $order->getOriginal('status'));
        }

        if ($order->wasChanged('driver_id')) {
            $notifications->orderDriverAssigned($order, $order->getOriginal('driver_id'));
        }

        if ($order->wasChanged('driver_delivery_status')) {
            $notifications->orderDriverDeliveryUpdated($order, $order->getOriginal('driver_delivery_status'));
        }

        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            if ($order->checkout_order_id === null) {
                app(RefundRequestService::class)->ensureForCancelledPaidOrder($order->fresh());
            }
        }

        if ($order->wasChanged('status') && $order->status === 'delivered') {
            if ($order->isRental()) {
                app(RentalPeriodService::class)->activateOnDelivery($order->fresh());
            }
        }

        if ($order->checkout_order_id !== null && $order->wasChanged('status')) {
            $checkout = $order->checkoutOrder;

            if ($checkout) {
                app(CheckoutRollupService::class)->sync($checkout->fresh());
            }
        }
    }
}
