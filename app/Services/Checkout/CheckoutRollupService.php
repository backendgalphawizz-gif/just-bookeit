<?php

namespace App\Services\Checkout;

use App\Models\CheckoutOrder;

class CheckoutRollupService
{
    /** @var list<string> */
    protected const TERMINAL_SUCCESS = ['delivered', 'returned', 're_delivered'];

    /** @var list<string> */
    protected const TERMINAL_CANCEL = ['cancelled', 'refunded'];

    public function sync(CheckoutOrder $checkout): CheckoutOrder
    {
        $subOrders = $checkout->subOrders()->get();

        if ($subOrders->isEmpty()) {
            return $checkout;
        }

        $statuses = $subOrders->pluck('status')->all();

        $allCancelled = collect($statuses)->every(fn ($s) => in_array($s, self::TERMINAL_CANCEL, true));
        $allSuccess = collect($statuses)->every(fn ($s) => in_array($s, self::TERMINAL_SUCCESS, true));
        $anyCancelled = collect($statuses)->contains(fn ($s) => in_array($s, self::TERMINAL_CANCEL, true));
        $anyDelivered = collect($statuses)->contains(fn ($s) => in_array($s, ['delivered', 're_delivered'], true));
        // Keep awaiting acceptance separate from in-progress so parent status
        // still reflects individual items waiting on vendor response.
        $anyAwaiting = collect($statuses)->contains(fn ($s) => in_array($s, ['new', 'pending_acceptance'], true));
        $anyInProgress = collect($statuses)->contains(fn ($s) => in_array($s, ['accepted', 'in_progress', 're_intransit', 'rework'], true));

        if ($checkout->payment_status === 'pending') {
            $checkout->status = 'new';
        } elseif ($allCancelled) {
            $checkout->status = 'cancelled';
        } elseif ($allSuccess) {
            $checkout->status = 'completed';
        } elseif ($anyCancelled && ($anyDelivered || $anyInProgress || $anyAwaiting)) {
            $checkout->status = 'partially_cancelled';
        } elseif ($anyDelivered && ! $allSuccess) {
            $checkout->status = 'partially_delivered';
        } elseif ($anyAwaiting && ! $anyInProgress && ! $anyDelivered) {
            $checkout->status = 'pending_acceptance';
        } elseif ($anyInProgress || $anyDelivered || $anyAwaiting) {
            $checkout->status = 'processing';
        } elseif (in_array($checkout->payment_status, ['success', 'advance_paid'], true)) {
            $checkout->status = 'pending_acceptance';
        }

        if ((float) $checkout->amount_refunded >= (float) $checkout->grand_total && $checkout->grand_total > 0) {
            $checkout->payment_status = 'refunded';
            $checkout->status = 'refunded';
        } elseif ((float) $checkout->amount_refunded > 0) {
            $checkout->payment_status = 'partially_refunded';
        }

        $checkout->save();

        return $checkout->fresh();
    }
}
