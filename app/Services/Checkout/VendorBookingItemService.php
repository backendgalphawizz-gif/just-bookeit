<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Services\Booking\BookingPricingService;
use App\Support\OrderDispatchSupport;
use App\Support\OrderItemStatusSupport;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VendorBookingItemService
{
    public function __construct(
        protected PartialRefundService $refunds,
        protected CheckoutRollupService $rollup
    ) {}

    public function acceptAll(Order $booking): Order
    {
        $this->assertRespondable($booking);

        return DB::transaction(function () use ($booking) {
            $booking->loadMissing('orderItems');

            if ($booking->orderItems->isEmpty()) {
                $booking->update(['status' => 'accepted']);

                return $this->freshBooking($booking);
            }

            foreach ($booking->orderItems->where('status', OrderItem::STATUS_PENDING) as $item) {
                $item->update([
                    'status' => OrderItem::STATUS_ACCEPTED,
                    'responded_at' => now(),
                    'cancellation_reason' => null,
                ]);
            }

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));

            return $this->freshBooking($booking);
        });
    }

    public function rejectAll(Order $booking, string $reason): array
    {
        $this->assertRespondable($booking);

        return DB::transaction(function () use ($booking, $reason) {
            $booking->loadMissing('orderItems');

            foreach ($booking->orderItems->where('status', '!=', OrderItem::STATUS_CANCELLED) as $item) {
                $item->update([
                    'status' => OrderItem::STATUS_CANCELLED,
                    'cancellation_reason' => $reason,
                    'responded_at' => now(),
                ]);
            }

            if ($booking->orderItems->isEmpty()) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);
            } else {
                $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']), $reason);
            }

            $refund = null;
            if ($booking->checkout_order_id !== null) {
                $refund = $this->refunds->forRejectedSubOrder($booking->fresh(), $reason);
                if ($booking->checkoutOrder) {
                    $this->rollup->sync($booking->checkoutOrder()->firstOrFail());
                }
            }

            return [
                'booking' => $this->freshBooking($booking),
                'refund' => $refund,
            ];
        });
    }

    public function acceptItem(Order $booking, OrderItem $item): Order
    {
        $this->assertItemBelongs($booking, $item);
        $this->assertRespondable($booking);

        if (! $item->canAccept()) {
            throw new InvalidArgumentException('This item cannot be accepted.');
        }

        return DB::transaction(function () use ($booking, $item) {
            $item->update([
                'status' => OrderItem::STATUS_ACCEPTED,
                'responded_at' => now(),
                'cancellation_reason' => null,
            ]);

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));

            return $this->freshBooking($booking);
        });
    }

    /** @return array{booking: Order, refund: ?Refund} */
    public function rejectItem(Order $booking, OrderItem $item, string $reason): array
    {
        $this->assertItemBelongs($booking, $item);
        $this->assertRespondable($booking);

        if (! $item->canReject()) {
            throw new InvalidArgumentException('This item cannot be rejected.');
        }

        return DB::transaction(function () use ($booking, $item, $reason) {
            $item->update([
                'status' => OrderItem::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'responded_at' => now(),
            ]);

            $refund = null;
            if ($booking->checkout_order_id !== null && in_array($booking->payment_status, ['success', 'advance_paid'], true)) {
                $refund = $this->refunds->forRejectedLineItem($booking->fresh(['orderItems', 'checkoutOrder']), $item->fresh(), $reason);
            }

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));

            return [
                'booking' => $this->freshBooking($booking),
                'refund' => $refund,
            ];
        });
    }

    /**
     * Update one line item status; booking status is recalculated from all items.
     */
    public function updateItemStatus(Order $booking, OrderItem $item, string $nextStatus): Order
    {
        $this->assertItemBelongs($booking, $item);

        if (! in_array($nextStatus, OrderItem::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid item status.');
        }

        // Rental outbound delivery completes as rental_active (not stuck on delivered).
        if ($nextStatus === 'delivered') {
            $nextStatus = OrderItemStatusSupport::statusAfterOutboundDelivery($item, $booking);
        }

        OrderItemStatusSupport::assertCanTransition($item, $nextStatus, $booking);

        return DB::transaction(function () use ($booking, $item, $nextStatus) {
            $previousItemStatus = $item->status;
            $previousBookingStatus = $booking->status;

            $payload = ['status' => $nextStatus];
            if (in_array($nextStatus, [OrderItem::STATUS_ACCEPTED, OrderItem::STATUS_CANCELLED], true)) {
                $payload['responded_at'] = now();
            }
            $item->update($payload);

            // Rework / return pickup: clear driver so admin can assign again.
            // Returned: product is back with vendor — clear active return driver.
            if (
                in_array($nextStatus, ['rework', 're_intransit', 'returned'], true)
                && $previousItemStatus !== $nextStatus
            ) {
                OrderDispatchSupport::resetItemDriverAssignment($item->fresh());
            }

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));
            $this->applyBookingSideEffects($booking->fresh(), $previousBookingStatus);

            return $this->freshBooking($booking);
        });
    }

    /**
     * Booking-level status update: push status onto all active items, then roll up.
     * Example: status=in_progress marks every accepted item in transit; booking becomes
     * in_progress only when all active items reach that stage (or slowest if mixed).
     */
    public function updateBookingStatus(Order $booking, string $nextStatus): Order
    {
        if (! in_array($nextStatus, Order::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid booking status.');
        }

        // Rental bookings: marking delivered advances straight to rental active.
        if ($nextStatus === 'delivered' && $booking->isRental()) {
            $nextStatus = 'rental_active';
        }

        return DB::transaction(function () use ($booking, $nextStatus) {
            $booking->loadMissing('orderItems');
            $previousBookingStatus = $booking->status;

            // Legacy / single-row bookings without line items: keep order-only transition.
            if ($booking->orderItems->isEmpty()) {
                OrderDispatchSupport::applyTransition($booking, $nextStatus);

                return $this->freshBooking($booking);
            }

            $active = $booking->orderItems->where('status', '!=', OrderItem::STATUS_CANCELLED);
            if ($active->isEmpty()) {
                throw new InvalidArgumentException('All items are cancelled; booking status cannot be changed.');
            }

            // Advance each active item that is behind / at a prior step toward nextStatus.
            foreach ($active as $item) {
                if ($item->status === $nextStatus) {
                    continue;
                }

                // Allow jump only along allowed graph from current item status.
                if (! OrderItemStatusSupport::canTransitionTo($item, $nextStatus, $booking)) {
                    throw new InvalidArgumentException(
                        'Item #'.$item->id.' ('.$item->status.') cannot move to '.$nextStatus.
                        '. Update items individually or bring all items to the same stage first.'
                    );
                }

                $previousItemStatus = $item->status;
                $item->update(['status' => $nextStatus]);

                if (
                    in_array($nextStatus, ['rework', 're_intransit'], true)
                    && $previousItemStatus !== $nextStatus
                ) {
                    OrderDispatchSupport::resetItemDriverAssignment($item->fresh());
                }
            }

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));
            $this->applyBookingSideEffects($booking->fresh(), $previousBookingStatus);

            return $this->freshBooking($booking);
        });
    }

    /**
     * Force-set active items to a status (used by driver/customer lifecycle), then roll up.
     * When $driver is set, only items assigned to that driver are updated (item-wise dispatch).
     */
    public function setActiveItemsStatus(Order $booking, string $nextStatus, ?\App\Models\Driver $driver = null): Order
    {
        return DB::transaction(function () use ($booking, $nextStatus, $driver) {
            $booking->loadMissing('orderItems');
            $previousBookingStatus = $booking->status;

            if ($nextStatus === 'delivered' && $booking->isRental()) {
                $nextStatus = 'rental_active';
            }

            if ($booking->orderItems->isEmpty()) {
                $previousBookingStatus = $booking->status;
                if ($booking->status !== $nextStatus) {
                    $booking->update(['status' => $nextStatus]);
                }
                $this->applyBookingSideEffects($booking->fresh(), $previousBookingStatus);

                return $this->freshBooking($booking);
            }

            OrderItemStatusSupport::applyStatusToActiveItems($booking, $nextStatus, force: true, driver: $driver);
            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));
            $this->applyBookingSideEffects($booking->fresh(), $previousBookingStatus);

            return $this->freshBooking($booking);
        });
    }

    public function syncBookingFromItems(Order $booking, ?string $cancellationReason = null): void
    {
        $booking->loadMissing('orderItems');
        $items = $booking->orderItems;

        if ($items->isEmpty()) {
            return;
        }

        $active = $items->where('status', '!=', OrderItem::STATUS_CANCELLED);

        // Recalculate amounts from remaining (non-cancelled) lines.
        $subtotal = round($active->sum(fn (OrderItem $item) => (float) $item->line_amount), 2);
        $gstPercent = BookingPricingService::gstPercent();
        $taxAmount = round($subtotal * ($gstPercent / 100), 2);
        $deliveryFee = $active->isEmpty() ? 0.0 : (float) ($booking->delivery_fee ?? 0);

        $resolved = OrderItemStatusSupport::resolveBookingFromItems($items, $booking);

        $updates = [
            'amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'delivery_fee' => $deliveryFee,
            'quantity' => max(0, (int) $active->sum('quantity')),
            'status' => $resolved['status'],
        ];

        if ($resolved['status'] === 'cancelled') {
            if ($cancellationReason) {
                $updates['cancellation_reason'] = $cancellationReason;
            } elseif (! filled($booking->cancellation_reason)) {
                $firstCancelled = $items->firstWhere('status', OrderItem::STATUS_CANCELLED);
                $updates['cancellation_reason'] = $firstCancelled?->cancellation_reason;
            }
        } elseif ($resolved['status'] !== 'cancelled') {
            // Clear cancel reason once booking is active again after partial reject.
            if ($active->isNotEmpty() && $resolved['status'] !== 'cancelled') {
                $updates['cancellation_reason'] = null;
            }
        }

        // Refresh display title from first active item when available.
        $firstActive = $active->first();
        if ($firstActive) {
            $booking->loadMissing('vendor');
            $updates['item_title'] = $active->count() > 1
                ? ($booking->vendor?->brand_name ?? 'Vendor').' — '.$active->count().' items'
                : $firstActive->title();
            $updates['portfolio_item_id'] = $firstActive->portfolio_item_id;
            $updates['item_image_path'] = $firstActive->item_snapshot['image_url'] ?? $booking->item_image_path;
            $updates['size'] = $firstActive->size();
            $updates['color'] = $firstActive->color();
        }

        $booking->update($updates);

        if ($booking->checkout_order_id) {
            $checkout = $booking->checkoutOrder()->first();
            if ($checkout) {
                $this->rollup->sync($checkout);
            }
        }
    }

    protected function applyBookingSideEffects(Order $booking, ?string $previousStatus = null): void
    {
        $booking->refresh();

        // Rework: release driver — vendor owns the item until they dispatch Return In Transit.
        if ($booking->status === 'rework' && $previousStatus !== 'rework') {
            OrderDispatchSupport::resetDriverAssignment($booking);
            $booking->save();

            return;
        }

        // Return In Transit: clear driver so admin can assign the return/rework pickup leg.
        if ($booking->status === 're_intransit' && $previousStatus !== 're_intransit') {
            OrderDispatchSupport::resetDriverAssignment($booking);
            $booking->save();
        } elseif ($booking->status === 'in_progress' && $previousStatus !== 'in_progress') {
            OrderDispatchSupport::prepareForTransit($booking);
            $booking->save();
        }
    }

    protected function assertRespondable(Order $booking): void
    {
        // Vendors may accept/reject before customer payment is confirmed.
        if (! in_array($booking->status, ['new', 'pending_acceptance', 'accepted'], true)) {
            throw new InvalidArgumentException('This booking cannot be updated.');
        }
    }

    protected function assertItemBelongs(Order $booking, OrderItem $item): void
    {
        if ((int) $item->order_id !== (int) $booking->id) {
            throw new InvalidArgumentException('Item does not belong to this booking.');
        }

        if ((int) $item->vendor_id !== (int) $booking->vendor_id) {
            throw new InvalidArgumentException('Item does not belong to this vendor.');
        }
    }

    protected function freshBooking(Order $booking): Order
    {
        return $booking->fresh([
            'customer.measurements',
            'vendor',
            'category',
            'driver',
            'review.customer',
            'orderItems',
            'checkoutOrder',
            'refunds',
        ]);
    }
}
