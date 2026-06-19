<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Driver;
use App\Models\Order;
use App\Support\OrderDispatchSupport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppPushNotificationService
{
    public function __construct(
        protected PushNotificationService $push
    ) {}

    public function orderCreated(Order $order): void
    {
        $order->loadMissing(['customer', 'vendor']);

        if (! $order->vendor) {
            return;
        }

        $customerName = $order->customer?->name ?? 'A customer';

        $this->safeSend(fn () => $this->push->sendToVendor(
            $order->vendor,
            'New booking received',
            "{$customerName} placed order {$order->order_number} for {$order->item_title}.",
            $this->orderPayload($order)
        ));
    }

    public function orderPaymentSucceeded(Order $order): void
    {
        $order->loadMissing(['customer', 'vendor']);

        if ($order->customer) {
            $this->safeSend(fn () => $this->push->sendToCustomer(
                $order->customer,
                'Payment successful',
                "Payment for order {$order->order_number} was completed successfully.",
                $this->orderPayload($order)
            ));
        }

        if ($order->vendor) {
            $this->safeSend(fn () => $this->push->sendToVendor(
                $order->vendor,
                'Payment received',
                "Payment confirmed for order {$order->order_number}.",
                $this->orderPayload($order)
            ));
        }
    }

    public function orderStatusChanged(Order $order, string $previousStatus): void
    {
        if ($previousStatus === $order->status) {
            return;
        }

        if ($previousStatus === 'new' && $order->status === 'pending_acceptance') {
            return;
        }

        $order->loadMissing(['customer', 'vendor', 'driver']);
        $label = Order::statusLabelFor($order->status);

        if ($order->customer) {
            if ($order->status === 'cancelled' && filled($order->cancellation_reason)) {
                $this->safeSend(fn () => $this->push->sendToCustomer(
                    $order->customer,
                    'Booking cancelled',
                    Str::limit((string) $order->cancellation_reason, 120),
                    $this->orderPayload($order, ['status' => 'cancelled'])
                ));
            } else {
                $this->safeSend(fn () => $this->push->sendToCustomer(
                    $order->customer,
                    'Booking status updated',
                    "Order {$order->order_number} is now {$label}.",
                    $this->orderPayload($order, ['status' => $order->status])
                ));
            }
        }

        if ($order->vendor && $this->shouldNotifyVendorOnStatusChange($order, $previousStatus)) {
            $this->safeSend(fn () => $this->push->sendToVendor(
                $order->vendor,
                'Booking status updated',
                "Order {$order->order_number} is now {$label}.",
                $this->orderPayload($order, ['status' => $order->status])
            ));
        }

        if (
            OrderDispatchSupport::isDispatchStatus($order->status)
            && $order->driver_id === null
            && ! OrderDispatchSupport::isDispatchStatus($previousStatus)
        ) {
            $this->notifyAvailableDrivers($order);
        }
    }

    public function orderDriverAssigned(Order $order, mixed $previousDriverId): void
    {
        $order->loadMissing(['customer', 'vendor', 'driver']);

        if ($order->driver_id === null) {
            if ($previousDriverId && $order->vendor) {
                $this->safeSend(fn () => $this->push->sendToVendor(
                    $order->vendor,
                    'Driver unassigned',
                    "Delivery for order {$order->order_number} needs a new driver.",
                    $this->orderPayload($order)
                ));
            }

            return;
        }

        if ((int) $previousDriverId === (int) $order->driver_id) {
            return;
        }

        if ($order->customer) {
            $driverName = $order->driver?->name ?? 'A driver';

            $this->safeSend(fn () => $this->push->sendToCustomer(
                $order->customer,
                'Driver assigned',
                "{$driverName} has been assigned to order {$order->order_number}.",
                $this->orderPayload($order)
            ));
        }

        if ($order->vendor && $order->driver) {
            $this->safeSend(fn () => $this->push->sendToVendor(
                $order->vendor,
                'Driver assigned',
                "{$order->driver->name} accepted delivery for order {$order->order_number}.",
                $this->orderPayload($order)
            ));
        }

        if ($order->driver) {
            $this->safeSend(fn () => $this->push->sendToDriver(
                $order->driver,
                'New delivery assigned',
                "You have been assigned order {$order->order_number}.",
                $this->orderPayload($order)
            ));
        }
    }

    public function orderDriverDeliveryUpdated(Order $order, mixed $previousDriverStatus): void
    {
        if ($order->driver_delivery_status === $previousDriverStatus) {
            return;
        }

        $order->loadMissing(['customer', 'vendor', 'driver']);

        $message = match ($order->driver_delivery_status) {
            Order::DRIVER_STATUS_PICKED_UP => 'Your order has been picked up for delivery.',
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'Your order is out for delivery.',
            Order::DRIVER_STATUS_RESCHEDULED => 'Your delivery has been rescheduled.',
            default => null,
        };

        if ($message && $order->customer) {
            $this->safeSend(fn () => $this->push->sendToCustomer(
                $order->customer,
                'Delivery update',
                "{$message} Order {$order->order_number}.",
                $this->orderPayload($order)
            ));
        }

        if ($order->driver_delivery_status === Order::DRIVER_STATUS_PICKED_UP && $order->vendor) {
            $this->safeSend(fn () => $this->push->sendToVendor(
                $order->vendor,
                'Order picked up',
                "Order {$order->order_number} has been picked up by the driver.",
                $this->orderPayload($order)
            ));
        }

        if ($order->driver_delivery_status === Order::DRIVER_STATUS_RESCHEDULED && $order->vendor) {
            $this->safeSend(fn () => $this->push->sendToVendor(
                $order->vendor,
                'Delivery rescheduled',
                "Delivery for order {$order->order_number} was rescheduled.",
                $this->orderPayload($order)
            ));
        }
    }

    public function chatMessageCreated(ChatMessage $message): void
    {
        $message->loadMissing('conversation.customer', 'conversation.vendor');
        $conversation = $message->conversation;

        if (! $conversation instanceof Conversation) {
            return;
        }

        $preview = $this->chatPreview($message);
        $payload = $this->chatPayload($conversation);

        if ($message->sender_type === ChatMessage::SENDER_CUSTOMER && $conversation->vendor) {
            $sender = $conversation->customer?->name ?? 'Customer';

            $this->safeSend(fn () => $this->push->sendToVendor(
                $conversation->vendor,
                "New message from {$sender}",
                $preview,
                $payload
            ));

            return;
        }

        if ($message->sender_type === ChatMessage::SENDER_VENDOR && $conversation->customer) {
            $sender = $conversation->vendor?->brand_name
                ?? $conversation->vendor?->shop_name
                ?? 'Designer';

            $this->safeSend(fn () => $this->push->sendToCustomer(
                $conversation->customer,
                "New message from {$sender}",
                $preview,
                $payload
            ));
        }
    }

    protected function notifyAvailableDrivers(Order $order): void
    {
        $query = Driver::query()
            ->where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '');

        if (filled($order->city)) {
            $query->where(function ($builder) use ($order) {
                $builder->where('city', $order->city)
                    ->orWhereNull('city')
                    ->orWhere('city', '');
            });
        }

        $drivers = $query->get();

        foreach ($drivers as $driver) {
            $this->safeSend(fn () => $this->push->sendToDriver(
                $driver,
                'New delivery available',
                "Order {$order->order_number} is ready for pickup.",
                $this->orderPayload($order)
            ));
        }
    }

    protected function shouldNotifyVendorOnStatusChange(Order $order, string $previousStatus): bool
    {
        if (in_array($order->status, ['delivered', 're_delivered', 'returned'], true)) {
            return true;
        }

        return ! in_array($previousStatus, ['new'], true);
    }

    protected function chatPreview(ChatMessage $message): string
    {
        if (filled($message->body)) {
            return Str::limit(trim((string) $message->body), 120);
        }

        return 'Sent an attachment';
    }

    /** @param  array<string, string>  $extra */
    protected function orderPayload(Order $order, array $extra = []): array
    {
        return [
            'type' => 'order',
            'order_id' => (string) $order->id,
            'type_id' => (string) $order->id,
            'chat' => '',
            ...$extra,
        ];
    }

    protected function chatPayload(Conversation $conversation): array
    {
        return [
            'type' => 'chat',
            'order_id' => '',
            'type_id' => (string) $conversation->id,
            'chat' => '1',
        ];
    }

    protected function safeSend(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            Log::warning('Push notification dispatch failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
