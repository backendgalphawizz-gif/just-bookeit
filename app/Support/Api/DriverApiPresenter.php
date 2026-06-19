<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Support\OrderDispatchSupport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DriverApiPresenter
{
    public static function paginator(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return CustomerApiPresenter::paginator($paginator, $mapper);
    }

    public static function driverSummary(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'driver_code' => $driver->driver_code,
            'name' => $driver->name,
            'mobile' => $driver->mobile,
            'email' => $driver->email,
            'city' => $driver->city,
            'vehicle_no' => $driver->vehicle_no,
            'status' => $driver->status,
            'is_verified' => (bool) $driver->is_verified,
            'profile_image_url' => $driver->profileImageUrl(),
            'wallet_balance' => (float) $driver->wallet_balance,
            'total_earnings' => (float) $driver->total_earnings,
        ];
    }

    /** @param array<string, mixed> $stats */
    public static function dashboardStats(array $stats): array
    {
        return [
            'total_earnings' => $stats['total_earnings'],
            'wallet_balance' => $stats['wallet_balance'],
            'currency' => 'INR',
            'assigned_deliveries' => $stats['assigned_deliveries'],
            'pending_deliveries' => $stats['pending_deliveries'],
            'completed_deliveries' => $stats['completed_deliveries'],
            'cancelled_deliveries' => $stats['cancelled_deliveries'],
        ];
    }

    public static function deliverySummary(Order $order, ?Driver $viewer = null): array
    {
        $order->loadMissing(['customer', 'vendor', 'category']);

        return [
            'id' => $order->id,
            'booking_id' => $order->order_number,
            'order_id' => $order->order_number,
            'order_number' => $order->order_number,
            'date' => $order->updated_at?->format('M d, Y'),
            'date_iso' => $order->updated_at?->toDateString(),
            'datetime_label' => $order->updated_at?->format('M d, Y, g:i A'),
            'status' => $order->status,
            'driver_delivery_status' => $order->driver_delivery_status,
            'status_label' => self::deliveryStatusLabel($order),
            'item_name' => $order->itemDisplayName(),
            'product_name' => $order->itemDisplayName(),
            'product_image_url' => $order->itemImageUrl(),
            'customer_name' => $order->customer?->name,
            'customer_image_url' => $order->customer?->profileImageUrl(),
            'imageUrl' => $order->customer?->profileImageUrl(),
            'amount' => $order->grandTotal(),
            'amount_label' => '₹'.number_format($order->grandTotal(), 0),
            'delivery_fee' => (float) ($order->delivery_fee ?? 0),
            'driver_earning' => $order->driver_earning !== null ? (float) $order->driver_earning : null,
            'payment_method' => $order->payment_method,
            'is_cod' => $order->isCod(),
            'payment_badge' => self::paymentBadge($order),
            'pickup_address' => self::pickupAddress($order),
            'delivery_address' => $order->delivery_address,
            'city' => $order->city,
            'pincode' => $order->pincode,
            'scheduled_for' => $order->driver_scheduled_for?->format('Y-m-d'),
            'scheduled_for_label' => $order->driver_scheduled_for?->format('M d, Y'),
            'can_accept' => self::canAccept($order, $viewer),
            'can_reject' => self::canReject($order, $viewer),
            'can_pickup' => self::canPickup($order, $viewer),
            'can_dispatch' => self::canDispatch($order, $viewer),
            'can_out_for_delivery' => self::canDispatch($order, $viewer),
            'can_deliver' => self::canDeliver($order, $viewer),
            'can_reschedule' => self::canReschedule($order, $viewer),
        ];
    }

    public static function deliveryDetail(Order $order, ?Driver $viewer = null): array
    {
        return [
            ...self::deliverySummary($order, $viewer),
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
                'mobile' => $order->customer?->mobile,
                'email' => $order->customer?->email,
                'profile_image_url' => $order->customer?->profileImageUrl(),
                'imageUrl' => $order->customer?->profileImageUrl(),
            ],
            'vendor' => $order->vendor ? [
                'id' => $order->vendor->id,
                'brand_name' => $order->vendor->brand_name,
                'shop_name' => $order->vendor->shop_name,
                'mobile' => $order->vendor->mobile,
                'city' => $order->vendor->city,
                'address' => CustomerApiPresenter::vendorAddress($order->vendor)['full_address'] ?? null,
            ] : null,
            'category' => $order->category ? CustomerApiPresenter::category($order->category) : null,
            'rental_start_date' => $order->rental_start_date?->format('Y-m-d'),
            'rental_end_date' => $order->rental_end_date?->format('Y-m-d'),
            'driver_assigned_at' => $order->driver_assigned_at?->format('M d, Y, g:i A'),
            'driver_pickup_at' => $order->driver_pickup_at?->format('M d, Y, g:i A'),
            'driver_delivered_at' => $order->driver_delivered_at?->format('M d, Y, g:i A'),
            'driver_rescheduled_at' => $order->driver_rescheduled_at?->format('M d, Y, g:i A'),
            'driver_rejection_reason' => $order->driver_rejection_reason,
            'delivery_proof_image_url' => $order->deliveryProofImageUrl(),
            'cod_collected_at' => $order->cod_collected_at?->format('M d, Y, g:i A'),
            'cod_collected_at_iso' => $order->cod_collected_at?->toIso8601String(),
            'requires_delivery_otp' => OrderDispatchSupport::isDispatchStatus($order->status)
                && in_array($order->driver_delivery_status, [
                    Order::DRIVER_STATUS_PICKED_UP,
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                    Order::DRIVER_STATUS_RESCHEDULED,
                ], true),
        ];
    }

    public static function walletTransaction(DriverWalletTransaction $transaction): array
    {
        $transaction->loadMissing(['order.customer']);

        return [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'transaction_id' => $transaction->transaction_code,
            'order_id' => $transaction->order?->order_number,
            'order_number' => $transaction->order?->order_number,
            'customer_name' => $transaction->order?->customer?->name,
            'customer_image_url' => $transaction->order?->customer?->profileImageUrl(),
            'imageUrl' => $transaction->order?->customer?->profileImageUrl(),
            'type' => strtoupper($transaction->direction),
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'amount_label' => '₹'.number_format((float) $transaction->amount, 0),
            'balance_after' => (float) $transaction->balance_after,
            'description' => $transaction->description,
            'created_at' => $transaction->created_at?->format('M d, Y, g:i A'),
            'created_at_iso' => $transaction->created_at?->toIso8601String(),
        ];
    }

    public static function deliveryStatusLabel(Order $order): string
    {
        if (in_array($order->status, ['delivered', 're_delivered'], true)) {
            return 'DELIVERED';
        }

        if ($order->status === 'cancelled') {
            return 'CANCELLED';
        }

        if (OrderDispatchSupport::isDispatchStatus($order->status) && $order->driver_id === null) {
            return 'NEW';
        }

        return match ($order->driver_delivery_status) {
            Order::DRIVER_STATUS_ACCEPTED => 'ACCEPTED',
            Order::DRIVER_STATUS_PICKED_UP => 'PICKUP',
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'DISPATCHED',
            Order::DRIVER_STATUS_RESCHEDULED => 'RESCHEDULED',
            default => strtoupper($order->statusLabel()),
        };
    }

    public static function paymentBadge(Order $order): ?string
    {
        if (! $order->isCod()) {
            return null;
        }

        if ($order->cod_collected_at) {
            return 'CASH COLLECTED';
        }

        return 'CASH COLLECT';
    }

    public static function pickupAddress(Order $order): ?string
    {
        if (filled($order->pickup_address)) {
            return $order->pickup_address;
        }

        $order->loadMissing('vendor');

        if (! $order->vendor) {
            return null;
        }

        return CustomerApiPresenter::vendorAddress($order->vendor)['full_address'] ?? null;
    }

    public static function canAccept(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer || ! OrderDispatchSupport::isDispatchStatus($order->status)) {
            return false;
        }

        if ($order->driver_id === null) {
            return true;
        }

        return (int) $order->driver_id === (int) $viewer->id
            && in_array($order->driver_delivery_status, [null, Order::DRIVER_STATUS_ACCEPTED], true);
    }

    public static function canReject(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if (OrderDispatchSupport::isDispatchStatus($order->status) && $order->driver_id === null) {
            return true;
        }

        return $order->driver_id === $viewer->id
            && OrderDispatchSupport::isDispatchStatus($order->status)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_ACCEPTED,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canPickup(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && OrderDispatchSupport::isDispatchStatus($order->status)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_ACCEPTED,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canDispatch(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && OrderDispatchSupport::isDispatchStatus($order->status)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canDeliver(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && OrderDispatchSupport::isDispatchStatus($order->status)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canReschedule(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && OrderDispatchSupport::isDispatchStatus($order->status)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_ACCEPTED,
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            ], true);
    }

    /** @return list<array<string, mixed>> */
    public static function sampleWalletTransactions(): array
    {
        $sampleImageUrl = 'https://picsum.photos/seed/jb-driver-wallet-demo/200/200';

        return [
            [
                'id' => 0,
                'transaction_code' => 'DWT-DEMO-001',
                'transaction_id' => 'DWT-DEMO-001',
                'order_id' => 'ORD-DEMO-1001',
                'order_number' => 'ORD-DEMO-1001',
                'customer_name' => 'Demo Customer',
                'customer_image_url' => $sampleImageUrl,
                'imageUrl' => $sampleImageUrl,
                'type' => 'CREDIT',
                'direction' => 'credit',
                'amount' => 150.0,
                'amount_label' => '₹150',
                'balance_after' => 2150.0,
                'description' => 'Delivery earning for ORD-DEMO-1001',
                'created_at' => now()->subDays(2)->format('M d, Y, g:i A'),
                'created_at_iso' => now()->subDays(2)->toIso8601String(),
                'is_sample' => true,
            ],
            [
                'id' => 0,
                'transaction_code' => 'DWT-DEMO-002',
                'transaction_id' => 'DWT-DEMO-002',
                'order_id' => 'ORD-DEMO-1002',
                'order_number' => 'ORD-DEMO-1002',
                'customer_name' => 'Demo Customer',
                'customer_image_url' => $sampleImageUrl,
                'imageUrl' => $sampleImageUrl,
                'type' => 'CREDIT',
                'direction' => 'credit',
                'amount' => 120.0,
                'amount_label' => '₹120',
                'balance_after' => 2000.0,
                'description' => 'Delivery earning for ORD-DEMO-1002',
                'created_at' => now()->subDay()->format('M d, Y, g:i A'),
                'created_at_iso' => now()->subDay()->toIso8601String(),
                'is_sample' => true,
            ],
            [
                'id' => 0,
                'transaction_code' => 'DWT-DEMO-003',
                'transaction_id' => 'DWT-DEMO-003',
                'order_id' => null,
                'order_number' => null,
                'customer_name' => null,
                'customer_image_url' => null,
                'imageUrl' => null,
                'type' => 'DEBIT',
                'direction' => 'debit',
                'amount' => 500.0,
                'amount_label' => '₹500',
                'balance_after' => 1880.0,
                'description' => 'Wallet withdrawal',
                'created_at' => now()->format('M d, Y, g:i A'),
                'created_at_iso' => now()->toIso8601String(),
                'is_sample' => true,
            ],
        ];
    }
}
