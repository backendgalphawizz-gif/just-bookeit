<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundHistory;
use App\Services\Booking\BookingPricingService;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Support\Facades\DB;

class PartialRefundService
{
    public function __construct(
        protected CheckoutRollupService $rollup,
        protected VendorWalletService $vendorWallet
    ) {}

    public function forRejectedSubOrder(Order $subOrder, string $reason): ?Refund
    {
        $subOrder->loadMissing(['checkoutOrder', 'orderItems', 'refunds']);

        $checkout = $subOrder->checkoutOrder;

        if (! $checkout || $checkout->payment_status === 'pending') {
            return null;
        }

        $alreadyRefunded = round((float) $subOrder->refunds->sum('amount'), 2);
        $amount = max(0, round($subOrder->grandTotal() - $alreadyRefunded, 2));

        // If item-level refunds already reduced order totals, leftover may sit on alreadyRefunded.
        // Fall back: refund any remaining book value when grandTotal was already zeroed.
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($subOrder, $checkout, $amount, $reason) {
            $refund = Refund::query()->create([
                'order_id' => $subOrder->id,
                'checkout_order_id' => $checkout->id,
                'customer_id' => $subOrder->customer_id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
                'source' => 'vendor_reject',
                'auto_processed' => true,
                'processed_at' => now(),
            ]);

            $this->recordHistory($refund, 'requested', 'Refund initiated automatically after vendor rejection.');
            $this->recordHistory($refund, 'processed', 'Refund processed automatically.', [
                'sub_order_number' => $subOrder->sub_order_number ?? $subOrder->order_number,
                'checkout_order_number' => $checkout->order_number,
            ]);

            $this->vendorWallet->debitForRefund($refund);

            $subOrder->update(['payment_status' => 'refunded']);

            $checkout->increment('amount_refunded', $amount);
            $checkout->refresh();

            $this->rollup->sync($checkout->fresh());

            return $refund->fresh(['histories']);
        });
    }

    public function forRejectedLineItem(Order $subOrder, OrderItem $item, string $reason): ?Refund
    {
        $subOrder->loadMissing(['checkoutOrder', 'orderItems']);

        $checkout = $subOrder->checkoutOrder;

        if (! $checkout || $checkout->payment_status === 'pending') {
            return null;
        }

        $lineAmount = round((float) $item->line_amount, 2);
        $taxAmount = round($lineAmount * (BookingPricingService::gstPercent() / 100), 2);
        $deliveryFee = 0.0;

        $remainingActive = $subOrder->orderItems
            ->where('id', '!=', $item->id)
            ->where('status', '!=', OrderItem::STATUS_CANCELLED);

        if ($remainingActive->isEmpty()) {
            $deliveryFee = (float) ($subOrder->delivery_fee ?? 0);
        }

        $amount = round($lineAmount + $taxAmount + $deliveryFee, 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($subOrder, $checkout, $item, $amount, $reason, $lineAmount, $taxAmount, $deliveryFee, $remainingActive) {
            $refund = Refund::query()->create([
                'order_id' => $subOrder->id,
                'checkout_order_id' => $checkout->id,
                'customer_id' => $subOrder->customer_id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
                'source' => 'vendor_item_reject',
                'auto_processed' => true,
                'processed_at' => now(),
            ]);

            $this->recordHistory($refund, 'requested', 'Refund initiated after vendor rejected a line item.');
            $this->recordHistory($refund, 'processed', 'Line-item refund processed automatically.', [
                'order_item_id' => $item->id,
                'item_title' => $item->title(),
                'line_amount' => $lineAmount,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'sub_order_number' => $subOrder->sub_order_number ?? $subOrder->order_number,
                'checkout_order_number' => $checkout->order_number,
            ]);

            $this->vendorWallet->debitForRefund($refund);

            $checkout->increment('amount_refunded', $amount);

            if ($remainingActive->isEmpty()) {
                $subOrder->update(['payment_status' => 'refunded']);
            }

            return $refund->fresh(['histories']);
        });
    }

    protected function recordHistory(Refund $refund, string $status, string $note, ?array $meta = null): void
    {
        RefundHistory::query()->create([
            'refund_id' => $refund->id,
            'status' => $status,
            'note' => $note,
            'meta' => $meta,
        ]);
    }
}
