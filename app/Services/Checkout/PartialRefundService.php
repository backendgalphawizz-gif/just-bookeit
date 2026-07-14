<?php

namespace App\Services\Checkout;

use App\Models\CheckoutOrder;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundHistory;
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
        $subOrder->loadMissing('checkoutOrder');

        $checkout = $subOrder->checkoutOrder;

        if (! $checkout || $checkout->payment_status === 'pending') {
            return null;
        }

        if ($subOrder->refund()->exists()) {
            return $subOrder->refund;
        }

        $amount = $subOrder->grandTotal();

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
