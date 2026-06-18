<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Refund\RefundRequestService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status') || $order->status !== 'cancelled') {
            return;
        }

        app(RefundRequestService::class)->ensureForCancelledPaidOrder($order->fresh());
    }
}
