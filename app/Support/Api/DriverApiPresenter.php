<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
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
            'order_number' => $order->order_number,
            'date' => $order->created_at?->format('M d, Y'),
            'date_iso' => $order->created_at?->toDateString(),
            'status' => $order->status,
            'driver_delivery_status' => $order->driver_delivery_status,
            'status_label' => self::deliveryStatusLabel($order),
            'item_name' => $order->itemDisplayName(),
            'product_name' => $order->itemDisplayName(),
            'product_image_url' => $order->itemImageUrl(),
            'customer_name' => $order->customer?->name,
            'amount' => (float) $order->amount,
            'amount_label' => '₹'.number_format((float) $order->amount, 0),
            'delivery_fee' => (float) ($order->delivery_fee ?? 0),
            'driver_earning' => $order->driver_earning !== null ? (float) $order->driver_earning : null,
            'pickup_address' => self::pickupAddress($order),
            'delivery_address' => $order->delivery_address,
            'city' => $order->city,
            'pincode' => $order->pincode,
            'can_accept' => self::canAccept($order, $viewer),
            'can_reject' => self::canReject($order, $viewer),
            'can_pickup' => self::canPickup($order, $viewer),
            'can_out_for_delivery' => self::canOutForDelivery($order, $viewer),
            'can_deliver' => self::canDeliver($order, $viewer),
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
            'requires_delivery_otp' => $order->status === 'in_transit'
                && in_array($order->driver_delivery_status, [
                    Order::DRIVER_STATUS_PICKED_UP,
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
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
        if ($order->status === 'delivered') {
            return 'DELIVERED';
        }

        if ($order->status === 'cancelled') {
            return 'CANCELLED';
        }

        if ($order->status === 'in_transit' && $order->driver_id === null) {
            return 'NEW';
        }

        return match ($order->driver_delivery_status) {
            Order::DRIVER_STATUS_ACCEPTED => 'ACCEPTED',
            Order::DRIVER_STATUS_PICKED_UP => 'PICKED UP',
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'OUT FOR DELIVERY',
            default => strtoupper($order->statusLabel()),
        };
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
        return $viewer
            && $order->status === 'in_transit'
            && $order->driver_id === null;
    }

    public static function canReject(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if ($order->status === 'in_transit' && $order->driver_id === null) {
            return true;
        }

        return $order->driver_id === $viewer->id
            && $order->status === 'in_transit'
            && $order->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED;
    }

    public static function canPickup(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && $order->status === 'in_transit'
            && $order->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED;
    }

    public static function canOutForDelivery(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && $order->status === 'in_transit'
            && $order->driver_delivery_status === Order::DRIVER_STATUS_PICKED_UP;
    }

    public static function canDeliver(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && $order->driver_id === $viewer->id
            && $order->status === 'in_transit'
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            ], true);
    }
}
