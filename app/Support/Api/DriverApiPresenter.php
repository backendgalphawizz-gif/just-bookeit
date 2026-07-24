<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderDispatchSupport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
        $order->loadMissing(['customer', 'vendor', 'category', 'orderItems']);
        $items = self::itemsForDriver($order, $viewer);
        $itemName = self::itemDisplayNameForDriver($order, $items);
        $itemImage = $items->first()?->displayImageUrl() ?: $order->itemImageUrl();
        $amount = self::amountForDriver($order, $items);
        $bookingStatus = BookingStatusPresenter::forBooking($order, vendorAliases: true);
        // Driver apps read root pickup/delivery. When any assigned item is on the
        // return leg (e.g. rental dress Return In Transit), swap customer ↔ vendor
        // even if the same driver also has an outbound item on this booking.
        // Per-item addresses on `items[]` stay leg-correct either way.
        $isReturnLeg = $items->contains(fn (OrderItem $item) => OrderDispatchSupport::isReturnLegItem($item))
            || ($items->isEmpty() && $order->status === 're_intransit');
        $addresses = OrderDispatchSupport::addressesForLeg($order, $isReturnLeg);

        return [
            'id' => $order->id,
            'booking_id' => $order->order_number,
            'order_id' => $order->order_number,
            'order_number' => $order->order_number,
            'date' => $order->updated_at?->format('M d, Y'),
            'date_iso' => $order->updated_at?->toDateString(),
            'datetime_label' => $order->updated_at?->format('M d, Y, g:i A'),
            'status' => $bookingStatus['status'],
            'status_raw' => $bookingStatus['status_raw'],
            'status_label' => $bookingStatus['status_label'],
            'driver_delivery_status' => $order->driver_delivery_status ?: $bookingStatus['driver_delivery_status'],
            'driver_status_label' => self::deliveryStatusLabel($order, $viewer),
            'is_picked_up' => $bookingStatus['is_picked_up'],
            'item_name' => $itemName,
            'product_name' => $itemName,
            'product_image_url' => $itemImage,
            'items_count' => max(1, $items->count() ?: ($order->orderItems->isEmpty() ? 1 : 0)),
            'customer_name' => $order->customer?->name,
            'customer_image_url' => $order->customer?->profileImageUrl(),
            'imageUrl' => $order->customer?->profileImageUrl(),
            'amount' => $amount,
            'amount_label' => '₹'.number_format($amount, 0),
            'delivery_fee' => (float) ($order->delivery_fee ?? 0),
            'driver_earning' => $order->driver_earning !== null ? (float) $order->driver_earning : null,
            'payment_method' => $order->payment_method,
            'is_cod' => $order->isCod(),
            'payment_badge' => self::paymentBadge($order),
            'pickup_address' => $addresses['pickup_address'],
            'delivery_address' => $addresses['delivery_address'],
            'is_return_leg' => $addresses['is_return_leg'],
            'delivery_leg' => $addresses['leg'],
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
        $order->loadMissing(['customer', 'vendor', 'category', 'orderItems']);
        $items = self::itemsForDriver($order, $viewer);
        $lineItems = $items->map(fn (OrderItem $item) => self::deliveryLineItem($item))->values()->all();

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
            'requires_delivery_otp' => false,
            'items' => $lineItems,
        ];
    }

    /**
     * Items this driver should see for the booking.
     * Assigned item drivers only see their own lines; others never appear.
     *
     * @return Collection<int, OrderItem>
     */
    public static function itemsForDriver(Order $order, ?Driver $viewer): Collection
    {
        $order->loadMissing('orderItems');
        $items = $order->orderItems;

        if ($items->isEmpty()) {
            return collect();
        }

        if (! $viewer) {
            return $items->values();
        }

        $mine = $items->where('driver_id', $viewer->id)->values();
        if ($mine->isNotEmpty()) {
            return $mine;
        }

        $anyItemAssigned = $items->contains(fn (OrderItem $item) => $item->driver_id !== null);
        if ($anyItemAssigned) {
            // Open pool: only unassigned items that are ready for dispatch.
            return $items
                ->whereNull('driver_id')
                ->filter(fn (OrderItem $item) => in_array($item->status, DriverDeliveryTab::activeDeliveryStatuses(), true))
                ->values();
        }

        // Legacy booking-level assignment (no per-item drivers).
        return $items->where('status', '!=', OrderItem::STATUS_CANCELLED)->values();
    }

    /** @return array<string, mixed> */
    public static function deliveryLineItem(OrderItem $item): array
    {
        $order = $item->relationLoaded('order') ? $item->order : $item->order()->first();
        $statusFields = BookingStatusPresenter::forItem($item, $order);
        $isReturnLeg = OrderDispatchSupport::isReturnLegItem($item);
        $addresses = $order
            ? OrderDispatchSupport::addressesForLeg($order, $isReturnLeg)
            : ['pickup_address' => null, 'delivery_address' => null, 'is_return_leg' => $isReturnLeg, 'leg' => $isReturnLeg ? 'return' : 'outbound'];

        return [
            'id' => $item->id,
            'portfolio_item_id' => $item->portfolio_item_id,
            'title' => $item->title(),
            'image_url' => $item->displayImageUrl(),
            'category' => $item->categoryName(),
            'size' => $item->size(),
            'color' => $item->color(),
            'variant_label' => $item->variantLabel(),
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'line_amount' => (float) $item->line_amount,
            'line_amount_label' => '₹'.number_format((float) $item->line_amount, 0),
            ...$statusFields,
            'pickup_address' => $addresses['pickup_address'],
            'delivery_address' => $addresses['delivery_address'],
            'is_return_leg' => $addresses['is_return_leg'],
            'delivery_leg' => $addresses['leg'],
            'driver_id' => $item->driver_id ? (int) $item->driver_id : null,
            'driver_assigned_at' => $item->driver_assigned_at?->toIso8601String(),
            'driver_pickup_at' => $item->driver_pickup_at?->toIso8601String(),
            'can_pickup' => in_array($item->driver_delivery_status, [
                null,
                Order::DRIVER_STATUS_ACCEPTED,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true) && in_array($item->status, ['accepted', 'in_progress', 're_intransit'], true),
            'can_dispatch' => in_array($item->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true),
            'can_deliver' => in_array($item->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true),
            'rental_start_date' => $item->rentalStartDate(),
            'rental_end_date' => $item->rentalEndDate(),
        ];
    }

    /** @param  Collection<int, OrderItem>  $items */
    protected static function itemDisplayNameForDriver(Order $order, Collection $items): string
    {
        if ($items->isEmpty()) {
            return $order->itemDisplayName();
        }

        if ($items->count() === 1) {
            return $items->first()->title();
        }

        return $items->first()->title().' +'.($items->count() - 1).' more';
    }

    /** @param  Collection<int, OrderItem>  $items */
    protected static function amountForDriver(Order $order, Collection $items): float
    {
        if ($items->isEmpty()) {
            return $order->grandTotal();
        }

        $order->loadMissing('orderItems');
        $anyItemAssigned = $order->orderItems->contains(fn (OrderItem $item) => $item->driver_id !== null);
        if (! $anyItemAssigned) {
            return $order->grandTotal();
        }

        return round((float) $items->sum(fn (OrderItem $item) => (float) $item->line_amount), 2);
    }

    public static function isDispatchReady(Order $order): bool
    {
        if (OrderDispatchSupport::isDispatchStatus($order->status)) {
            return true;
        }

        $order->loadMissing('orderItems');

        return $order->orderItems->contains(
            fn (OrderItem $item) => in_array($item->status, DriverDeliveryTab::activeDeliveryStatuses(), true)
        );
    }

    public static function driverOwnsDelivery(Order $order, Driver $viewer): bool
    {
        if ((int) $order->driver_id === (int) $viewer->id) {
            return true;
        }

        $order->loadMissing('orderItems');

        return $order->orderItems->contains(
            fn (OrderItem $item) => (int) $item->driver_id === (int) $viewer->id
        );
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

    public static function deliveryStatusLabel(Order $order, ?Driver $viewer = null): string
    {
        $order->loadMissing('orderItems');

        // Item-wise: label is DELIVERED only when every item assigned to this driver is done.
        if ($viewer && self::viewerHasAssignedItems($order, $viewer)) {
            $mine = $order->orderItems->where('driver_id', $viewer->id)->values();
            if ($mine->isNotEmpty() && self::driverItemsAllDelivered($mine)) {
                return 'DELIVERED';
            }

            $active = $mine->filter(
                fn (OrderItem $item) => ! in_array($item->status, self::driverItemDoneStatuses(), true)
            );

            if ($active->isNotEmpty()) {
                $statuses = $active->pluck('driver_delivery_status')->filter()->values();
                $rank = [
                    Order::DRIVER_STATUS_ACCEPTED => 1,
                    Order::DRIVER_STATUS_RESCHEDULED => 2,
                    Order::DRIVER_STATUS_PICKED_UP => 3,
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 4,
                ];
                $furthest = $statuses->sortByDesc(fn ($s) => $rank[$s] ?? 0)->first();

                return match ($furthest) {
                    Order::DRIVER_STATUS_ACCEPTED => 'ACCEPTED',
                    Order::DRIVER_STATUS_PICKED_UP => 'PICKUP',
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'DISPATCHED',
                    Order::DRIVER_STATUS_RESCHEDULED => 'RESCHEDULED',
                    default => 'ASSIGNED',
                };
            }
        }

        if (in_array($order->status, ['delivered', 're_delivered', 'returned', 'completed'], true)) {
            return 'DELIVERED';
        }

        if ($order->status === 'cancelled') {
            return 'CANCELLED';
        }

        if (self::isDispatchReady($order) && $order->driver_id === null && ! self::viewerHasAssignedItems($order, $viewer)) {
            return 'NEW';
        }

        if (
            self::isDispatchReady($order)
            && $order->driver_id !== null
            && $order->driver_delivery_status === null
            && $order->driver_delivered_at !== null
        ) {
            return 'DELIVERED';
        }

        if (
            self::isDispatchReady($order)
            && $order->driver_id !== null
            && $order->driver_delivery_status === null
        ) {
            return 'ASSIGNED';
        }

        return match ($order->driver_delivery_status) {
            Order::DRIVER_STATUS_ACCEPTED => 'ACCEPTED',
            Order::DRIVER_STATUS_PICKED_UP => 'PICKUP',
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'DISPATCHED',
            Order::DRIVER_STATUS_RESCHEDULED => 'RESCHEDULED',
            default => strtoupper($order->statusLabel()),
        };
    }

    /** @return list<string> */
    public static function driverItemDoneStatuses(): array
    {
        return ['delivered', 'returned', 're_delivered', 'completed', 'cancelled'];
    }

    /** @param  Collection<int, OrderItem>  $items */
    public static function driverItemsAllDelivered(Collection $items): bool
    {
        $active = $items->where('status', '!=', OrderItem::STATUS_CANCELLED);
        if ($active->isEmpty()) {
            return false;
        }

        return $active->every(
            fn (OrderItem $item) => in_array($item->status, ['delivered', 'returned', 're_delivered', 'completed'], true)
        );
    }

    protected static function viewerHasAssignedItems(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        $order->loadMissing('orderItems');

        return $order->orderItems->contains(
            fn (OrderItem $item) => (int) $item->driver_id === (int) $viewer->id
        );
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
        if (! $viewer || ! self::isDispatchReady($order)) {
            return false;
        }

        // Already accepted (including admin assignment) — no accept step needed.
        if (
            self::driverOwnsDelivery($order, $viewer)
            && $order->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED
        ) {
            return false;
        }

        if (self::viewerHasAssignedItems($order, $viewer)) {
            return $order->driver_delivery_status === null;
        }

        if ($order->driver_id === null) {
            return true;
        }

        return (int) $order->driver_id === (int) $viewer->id
            && $order->driver_delivery_status === null;
    }

    public static function canReject(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if (self::isDispatchReady($order) && $order->driver_id === null && ! self::viewerHasAssignedItems($order, $viewer)) {
            return true;
        }

        return self::driverOwnsDelivery($order, $viewer)
            && self::isDispatchReady($order)
            && in_array($order->driver_delivery_status, [
                null,
                Order::DRIVER_STATUS_ACCEPTED,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canPickup(Order $order, ?Driver $viewer): bool
    {
        if (! $viewer || ! self::driverOwnsDelivery($order, $viewer) || ! self::isDispatchReady($order)) {
            return false;
        }

        // Admin-assigned (accepted) or legacy null-but-owned — pickup allowed without accept.
        return in_array($order->driver_delivery_status, [
            null,
            Order::DRIVER_STATUS_ACCEPTED,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true);
    }

    public static function canDispatch(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && self::driverOwnsDelivery($order, $viewer)
            && self::isDispatchReady($order)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canDeliver(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && self::driverOwnsDelivery($order, $viewer)
            && self::isDispatchReady($order)
            && in_array($order->driver_delivery_status, [
                Order::DRIVER_STATUS_PICKED_UP,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                Order::DRIVER_STATUS_RESCHEDULED,
            ], true);
    }

    public static function canReschedule(Order $order, ?Driver $viewer): bool
    {
        return $viewer
            && self::driverOwnsDelivery($order, $viewer)
            && self::isDispatchReady($order)
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
