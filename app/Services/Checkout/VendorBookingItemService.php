<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Services\Booking\BookingPricingService;
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

            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

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
            if ($booking->checkout_order_id !== null && $booking->payment_status === 'success') {
                $refund = $this->refunds->forRejectedLineItem($booking->fresh(['orderItems', 'checkoutOrder']), $item->fresh(), $reason);
            }

            $this->syncBookingFromItems($booking->fresh(['orderItems', 'checkoutOrder']));

            return [
                'booking' => $this->freshBooking($booking),
                'refund' => $refund,
            ];
        });
    }

    public function syncBookingFromItems(Order $booking): void
    {
        $booking->loadMissing('orderItems');
        $items = $booking->orderItems;

        if ($items->isEmpty()) {
            return;
        }

        $active = $items->where('status', '!=', OrderItem::STATUS_CANCELLED);
        $pending = $items->where('status', OrderItem::STATUS_PENDING);
        $accepted = $items->where('status', OrderItem::STATUS_ACCEPTED);

        // Recalculate amounts from remaining (non-cancelled) lines.
        $subtotal = round($active->sum(fn (OrderItem $item) => (float) $item->line_amount), 2);
        $gstPercent = BookingPricingService::gstPercent();
        $taxAmount = round($subtotal * ($gstPercent / 100), 2);
        $deliveryFee = $active->isEmpty() ? 0.0 : (float) ($booking->delivery_fee ?? 0);

        $updates = [
            'amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'delivery_fee' => $deliveryFee,
            'quantity' => max(0, (int) $active->sum('quantity')),
        ];

        if ($active->isEmpty()) {
            $updates['status'] = 'cancelled';
            if (! filled($booking->cancellation_reason)) {
                $firstCancelled = $items->firstWhere('status', OrderItem::STATUS_CANCELLED);
                $updates['cancellation_reason'] = $firstCancelled?->cancellation_reason;
            }
        } elseif ($pending->isEmpty() && $accepted->isNotEmpty()) {
            $updates['status'] = 'accepted';
            $updates['cancellation_reason'] = null;
        } elseif ($pending->isNotEmpty()) {
            // Keep awaiting response while some items are still pending.
            if (in_array($booking->status, ['new', 'pending_acceptance', 'accepted'], true)) {
                $updates['status'] = 'pending_acceptance';
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

    protected function assertRespondable(Order $booking): void
    {
        if ($booking->payment_status !== 'success') {
            throw new InvalidArgumentException('This booking is not paid yet.');
        }

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
