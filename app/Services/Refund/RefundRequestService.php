<?php

namespace App\Services\Refund;

use App\Models\Order;
use App\Models\Refund;
use App\Services\Vendor\VendorWalletService;

class RefundRequestService
{
    public function ensureForCancelledPaidOrder(Order $order): ?Refund
    {
        if ($order->checkout_order_id !== null) {
            return null;
        }

        if ($order->status !== 'cancelled' || ! in_array($order->payment_status, ['success', 'advance_paid'], true)) {
            return null;
        }

        if ($order->refund()->exists()) {
            return $order->refund;
        }

        $amount = round((float) ($order->amount_paid ?? 0), 2);
        if ($amount <= 0) {
            $amount = $order->grandTotal();
        }

        if ($amount <= 0) {
            return null;
        }

        $reason = filled($order->cancellation_reason)
            ? trim((string) $order->cancellation_reason)
            : 'Order cancelled — customer payment refund required';

        $refund = Refund::query()->create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'requested',
        ]);

        app(VendorWalletService::class)->debitForRefund($refund);

        return $refund;
    }
}
