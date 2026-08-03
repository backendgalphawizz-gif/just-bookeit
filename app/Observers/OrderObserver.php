<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Admin\AdminInboxNotificationService;
use App\Services\AppPushNotificationService;
use App\Services\Booking\BookingPaymentService;
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

        if ($order->wasChanged('payment_status') && in_array($order->payment_status, ['success', 'advance_paid'], true)) {
            $previousPayment = (string) $order->getOriginal('payment_status');
            if (! in_array($previousPayment, ['success', 'advance_paid'], true)) {
                $notifications->orderPaymentSucceeded($order);
            }
        }

        // Payment (online) or COD placement auto-dispatches — no admin "Send to designer".
        if (
            $order->wasChanged('status')
            && $order->status === 'pending_acceptance'
            && (string) $order->getOriginal('status') === 'new'
        ) {
            $notifications->orderDispatchedToVendor($order);
        }

        if ($order->wasChanged('status')) {
            $notifications->orderStatusChanged($order, (string) $order->getOriginal('status'));
        }

        if ($order->wasChanged('driver_id')) {
            $notifications->orderDriverAssigned($order, $order->getOriginal('driver_id'));
        }

        if ($order->wasChanged('status') || $order->wasChanged('driver_id')) {
            app(AdminInboxNotificationService::class)->notifyOrderAwaitingDriver($order);
        }

        if ($order->wasChanged('driver_delivery_status')) {
            $notifications->orderDriverDeliveryUpdated($order, $order->getOriginal('driver_delivery_status'));
        }

        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            if ($order->checkout_order_id === null) {
                app(RefundRequestService::class)->ensureForCancelledPaidOrder($order->fresh());
            }
        }

        if ($order->wasChanged('status') && in_array($order->status, ['delivered', 'rental_active'], true)) {
            if ($order->isRental()) {
                app(RentalPeriodService::class)->activateOnDelivery($order->fresh());
            }

            // COD cash is collected on delivery — mark paid + credit vendor (no admin step).
            app(BookingPaymentService::class)->settleCodOnDelivery($order->fresh());
        }

        if ($order->checkout_order_id !== null && $order->wasChanged('status')) {
            $checkout = $order->checkoutOrder;

            if ($checkout) {
                app(CheckoutRollupService::class)->sync($checkout->fresh());
            }
        }
    }
}
