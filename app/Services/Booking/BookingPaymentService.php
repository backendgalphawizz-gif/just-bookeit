<?php

namespace App\Services\Booking;

use App\Models\CheckoutOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\CheckoutRollupService;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingPaymentService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ADVANCE_PAID = 'advance_paid';

    public const STATUS_SUCCESS = 'success';

    /** Booking statuses where remaining balance can be collected. */
    public const REMAINING_DUE_STATUSES = [
        'delivered',
        'returned',
        'rework',
        're_intransit',
        're_delivered',
    ];

    public function __construct(
        protected VendorWalletService $wallet,
        protected CheckoutRollupService $rollup
    ) {}

    /** @return array<string, mixed> */
    public function summaryForOrder(Order $order): array
    {
        $order->loadMissing(['portfolioItem', 'orderItems.portfolioItem']);
        $pricing = BookingPricingService::fromOrder($order);
        $total = round((float) ($pricing['total_amount'] ?? $order->grandTotal()), 2);
        $advanceRequired = min(
            $this->requiredAdvanceForOrder($order, $pricing),
            $total
        );
        $accountingPaid = $this->resolvedAmountPaid(
            (float) ($order->amount_paid ?? 0),
            (string) $order->payment_status,
            $total,
            $advanceRequired
        );
        $isCodPending = $order->isCod() && $order->payment_status === self::STATUS_PENDING;
        // Customer apps expect amount_paid for COD to show the amount due on delivery.
        $amountPaid = $isCodPending ? $total : $accountingPaid;
        $remaining = $isCodPending
            ? 0.0
            : round(max(0, $total - $accountingPaid), 2);
        $remainingUnlocked = $this->remainingPaymentUnlocked($order->status);
        $payableNow = $isCodPending
            ? 0.0
            : $this->payableNow(
                $order->payment_status,
                $advanceRequired,
                $total,
                $accountingPaid,
                $remainingUnlocked
            );
        $phase = $isCodPending
            ? 'cod_pending'
            : $this->phase($order->payment_status, $advanceRequired, $remaining, $remainingUnlocked);

        return [
            ...$pricing,
            'advance_amount' => $advanceRequired,
            'amount_paid' => $amountPaid,
            'remaining_amount' => $remaining,
            'payable_now' => $payableNow,
            'payment_phase' => $phase,
            'payment_status' => $order->payment_status,
            'requires_advance' => $advanceRequired > 0,
            'remaining_payment_unlocked' => $remainingUnlocked,
            'is_fully_paid' => in_array($order->payment_status, [self::STATUS_SUCCESS], true)
                || (! $isCodPending && $remaining <= 0.0),
            'can_pay' => $payableNow > 0
                && ! in_array($order->payment_status, [self::STATUS_SUCCESS, 'refunded'], true)
                && ! $this->isCodConfirmedOrder($order),
            'pay_label' => $isCodPending
                ? 'Pay ₹'.number_format($total, 0).' on delivery'
                : $this->payLabel($order->payment_status, $advanceRequired, $payableNow, $phase),
        ];
    }

    /** @return array<string, mixed> */
    public function summaryForCheckout(CheckoutOrder $checkout): array
    {
        $checkout->loadMissing(['subOrders.orderItems.portfolioItem', 'subOrders.portfolioItem']);
        $total = round((float) $checkout->grand_total, 2);
        $advanceRequired = min(
            $this->requiredAdvanceForCheckout($checkout),
            $total
        );
        $accountingPaid = $this->resolvedAmountPaid(
            (float) ($checkout->amount_paid ?? 0),
            (string) $checkout->payment_status,
            $total,
            $advanceRequired
        );
        $isCodPending = $checkout->payment_method === 'cod' && $checkout->payment_status === self::STATUS_PENDING;
        $amountPaid = $isCodPending ? $total : $accountingPaid;
        $remaining = $isCodPending
            ? 0.0
            : round(max(0, $total - $accountingPaid), 2);
        $remainingUnlocked = $checkout->subOrders->contains(
            fn (Order $sub) => $this->remainingPaymentUnlocked($sub->status)
        ) || $this->remainingPaymentUnlocked($checkout->status);
        $payableNow = $isCodPending
            ? 0.0
            : $this->payableNow(
                $checkout->payment_status,
                $advanceRequired,
                $total,
                $accountingPaid,
                $remainingUnlocked
            );
        $phase = $isCodPending
            ? 'cod_pending'
            : $this->phase($checkout->payment_status, $advanceRequired, $remaining, $remainingUnlocked);

        return [
            'subtotal' => round((float) $checkout->amount, 2),
            'shipping_fee' => round((float) $checkout->delivery_fee, 2),
            'tax_amount' => round((float) $checkout->tax_amount, 2),
            'tax_percent' => BookingPricingService::gstPercent(),
            'tax_included_in_payable' => false,
            'advance_amount' => $advanceRequired,
            'amount_paid' => $amountPaid,
            'remaining_amount' => $remaining,
            'payable_now' => $payableNow,
            'total_amount' => $total,
            'grand_total' => $total,
            'currency' => 'INR',
            'payment_phase' => $phase,
            'payment_status' => $checkout->payment_status,
            'requires_advance' => $advanceRequired > 0,
            'remaining_payment_unlocked' => $remainingUnlocked,
            'is_fully_paid' => in_array($checkout->payment_status, [self::STATUS_SUCCESS], true)
                || (! $isCodPending && $remaining <= 0.0),
            'can_pay' => $payableNow > 0
                && ! in_array($checkout->payment_status, [self::STATUS_SUCCESS, 'refunded', 'partially_refunded'], true)
                && ! $this->isCodConfirmedCheckout($checkout),
            'pay_label' => $isCodPending
                ? 'Pay ₹'.number_format($total, 0).' on delivery'
                : $this->payLabel($checkout->payment_status, $advanceRequired, $payableNow, $phase),
        ];
    }

    public function payOrder(Order $order, string $paymentMethod): Order
    {
        if ($paymentMethod === 'cod') {
            return $this->confirmCodOrder($order);
        }

        $summary = $this->summaryForOrder($order);

        if (! $summary['can_pay']) {
            throw new InvalidArgumentException(
                $summary['is_fully_paid']
                    ? 'Payment already completed for this booking.'
                    : 'No payment is due for this booking right now.'
            );
        }

        $payableNow = (float) $summary['payable_now'];
        $total = (float) $summary['total_amount'];
        $newPaid = round(min($total, (float) ($order->amount_paid ?? 0) + $payableNow), 2);
        $nextStatus = $newPaid + 0.009 >= $total ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;

        $order->update([
            'advance_amount' => min((float) $summary['advance_amount'], $total),
            'amount_paid' => $newPaid,
            'payment_status' => $nextStatus,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
            // Auto-dispatch to vendor after payment — no admin "Send to designer" step.
            'status' => in_array($order->status, ['new', 'pending_acceptance'], true)
                ? 'pending_acceptance'
                : $order->status,
        ]);

        $order = $order->fresh(['vendor', 'category', 'orderItems.portfolioItem', 'portfolioItem']);

        if ($nextStatus === self::STATUS_SUCCESS) {
            $this->wallet->creditFromPayment($order);
        }

        return $order;
    }

    /**
     * COD: send booking straight to the vendor. Cash stays pending until delivery —
     * no admin payment approval and no wallet credit yet.
     */
    public function confirmCodOrder(Order $order): Order
    {
        if ($order->isCod() && $order->status !== 'new' && ! in_array($order->status, ['cancelled', 'refunded'], true)) {
            return $order->fresh(['vendor', 'category', 'orderItems.portfolioItem', 'portfolioItem']);
        }

        if (in_array($order->payment_status, [self::STATUS_SUCCESS, self::STATUS_ADVANCE_PAID], true)) {
            throw new InvalidArgumentException('Payment already completed for this booking.');
        }

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            throw new InvalidArgumentException('This booking cannot be updated.');
        }

        $summary = $this->summaryForOrder($order);

        $order->update([
            'advance_amount' => $summary['advance_amount'],
            'payment_method' => 'cod',
            'payment_status' => self::STATUS_PENDING,
            'amount_paid' => (float) ($order->amount_paid ?? 0),
            'paid_at' => null,
            'status' => in_array($order->status, ['new', 'pending_acceptance'], true)
                ? 'pending_acceptance'
                : $order->status,
        ]);

        return $order->fresh(['vendor', 'category', 'orderItems.portfolioItem', 'portfolioItem']);
    }

    public function payCheckout(CheckoutOrder $checkout, string $paymentMethod): CheckoutOrder
    {
        if ($paymentMethod === 'cod') {
            return $this->confirmCodCheckout($checkout);
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $summary = $this->summaryForCheckout($checkout);

            if (! $summary['can_pay']) {
                throw new InvalidArgumentException(
                    $summary['is_fully_paid']
                        ? 'Payment already completed for this checkout.'
                        : 'No payment is due for this checkout right now.'
                );
            }

            $payableNow = (float) $summary['payable_now'];
            $total = (float) $summary['total_amount'];
            $newPaid = round(min($total, (float) ($checkout->amount_paid ?? 0) + $payableNow), 2);
            $nextStatus = $newPaid + 0.009 >= $total ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;
            $checkoutTotal = max(0.01, $total);

            $checkout->update([
                'advance_amount' => min((float) $summary['advance_amount'], $total),
                'amount_paid' => $newPaid,
                'payment_status' => $nextStatus,
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
                'status' => in_array($checkout->status, ['new', 'pending_acceptance'], true)
                    ? 'pending_acceptance'
                    : $checkout->status,
            ]);

            $allocated = 0.0;
            $subs = $checkout->subOrders()->with(['orderItems.portfolioItem', 'portfolioItem'])->get();
            $lastIndex = $subs->count() - 1;

            foreach ($subs->values() as $index => $subOrder) {
                $subSummary = $this->summaryForOrder($subOrder);
                $subTotal = (float) $subSummary['total_amount'];

                if ($index === $lastIndex) {
                    $subPay = round($payableNow - $allocated, 2);
                } else {
                    $subPay = round($payableNow * ($subTotal / $checkoutTotal), 2);
                    $allocated += $subPay;
                }

                $subPaid = round(min($subTotal, (float) ($subOrder->amount_paid ?? 0) + max(0, $subPay)), 2);
                $subStatus = $subPaid + 0.009 >= $subTotal ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;
                if ($nextStatus === self::STATUS_SUCCESS) {
                    $subStatus = self::STATUS_SUCCESS;
                    $subPaid = max($subPaid, $subTotal);
                }

                $subOrder->update([
                    'advance_amount' => min((float) $subSummary['advance_amount'], $subTotal),
                    'amount_paid' => $subPaid,
                    'payment_status' => $subStatus,
                    'payment_method' => $paymentMethod,
                    'paid_at' => now(),
                    // Each vendor sub-order is sent directly for designer acceptance.
                    'status' => in_array($subOrder->status, ['new', 'pending_acceptance'], true)
                        ? 'pending_acceptance'
                        : $subOrder->status,
                ]);

                if ($subStatus === self::STATUS_SUCCESS) {
                    $this->wallet->creditFromPayment($subOrder->fresh());
                }
            }

            return $this->rollup->sync($checkout->fresh([
                'subOrders.orderItems.portfolioItem',
                'subOrders.vendor',
                'subOrders.category',
            ]));
        });
    }

    public function confirmCodCheckout(CheckoutOrder $checkout): CheckoutOrder
    {
        return DB::transaction(function () use ($checkout) {
            if (
                $checkout->payment_method === 'cod'
                && $checkout->status !== 'new'
                && ! in_array($checkout->status, ['cancelled', 'refunded'], true)
            ) {
                return $checkout->fresh([
                    'subOrders.orderItems.portfolioItem',
                    'subOrders.vendor',
                    'subOrders.category',
                ]);
            }

            if (in_array($checkout->payment_status, [self::STATUS_SUCCESS, self::STATUS_ADVANCE_PAID], true)) {
                throw new InvalidArgumentException('Payment already completed for this checkout.');
            }

            if (in_array($checkout->status, ['cancelled', 'refunded'], true)) {
                throw new InvalidArgumentException('This checkout cannot be updated.');
            }

            $summary = $this->summaryForCheckout($checkout);

            $checkout->update([
                'advance_amount' => $summary['advance_amount'],
                'payment_method' => 'cod',
                'payment_status' => self::STATUS_PENDING,
                'amount_paid' => (float) ($checkout->amount_paid ?? 0),
                'paid_at' => null,
                'status' => in_array($checkout->status, ['new', 'pending_acceptance'], true)
                    ? 'pending_acceptance'
                    : $checkout->status,
            ]);

            foreach ($checkout->subOrders()->with(['orderItems.portfolioItem', 'portfolioItem'])->get() as $subOrder) {
                $this->confirmCodOrder($subOrder);
            }

            return $this->rollup->sync($checkout->fresh([
                'subOrders.orderItems.portfolioItem',
                'subOrders.vendor',
                'subOrders.category',
            ]));
        });
    }

    /** Mark COD cash collected and credit the vendor wallet (on delivery). */
    public function settleCodOnDelivery(Order $order): void
    {
        if (! $order->isCod() || $order->payment_status !== self::STATUS_PENDING) {
            return;
        }

        $total = round((float) ($this->summaryForOrder($order)['total_amount'] ?? $order->grandTotal()), 2);

        Order::withoutEvents(function () use ($order, $total): void {
            $order->update([
                'payment_status' => self::STATUS_SUCCESS,
                'amount_paid' => max((float) ($order->amount_paid ?? 0), $total),
                'paid_at' => $order->paid_at ?? now(),
            ]);
        });

        $this->wallet->creditFromPayment($order->fresh());
    }

    /** @param array<string, mixed> $pricing */
    public function requiredAdvanceForOrder(Order $order, array $pricing = []): float
    {
        $order->loadMissing(['orderItems.portfolioItem', 'portfolioItem']);

        if ($order->orderItems->isNotEmpty()) {
            return round($order->orderItems->sum(fn (OrderItem $item) => $item->advanceAmount()), 2);
        }

        if (isset($pricing['advance_amount'])) {
            return round((float) $pricing['advance_amount'], 2);
        }

        return $order->portfolioItem?->advance_amount !== null
            ? round((float) $order->portfolioItem->advance_amount, 2)
            : round((float) ($order->advance_amount ?? 0), 2);
    }

    public function requiredAdvanceForCheckout(CheckoutOrder $checkout): float
    {
        $checkout->loadMissing(['subOrders.orderItems.portfolioItem', 'subOrders.portfolioItem']);

        return round($checkout->subOrders->sum(
            fn (Order $sub) => $this->requiredAdvanceForOrder($sub)
        ), 2);
    }

    /**
     * Prefer the stored paid total; if older rows only flipped payment_status,
     * infer what the customer has already paid.
     */
    protected function resolvedAmountPaid(
        float $storedPaid,
        string $paymentStatus,
        float $total,
        float $advanceRequired
    ): float {
        $paid = round(max(0, $storedPaid), 2);

        if ($paid > 0) {
            return $paid;
        }

        if (in_array($paymentStatus, [self::STATUS_SUCCESS, 'refunded', 'partially_refunded'], true)) {
            return round(max(0, $total), 2);
        }

        if ($paymentStatus === self::STATUS_ADVANCE_PAID) {
            return round(max(0, $advanceRequired > 0 ? $advanceRequired : $total), 2);
        }

        return 0.0;
    }

    protected function payableNow(
        string $paymentStatus,
        float $advanceRequired,
        float $total,
        float $amountPaid,
        bool $remainingUnlocked
    ): float {
        if (in_array($paymentStatus, [self::STATUS_SUCCESS, 'refunded', 'partially_refunded'], true)) {
            return 0.0;
        }

        $remaining = round(max(0, $total - $amountPaid), 2);

        if ($paymentStatus === self::STATUS_PENDING) {
            // Charge advance at booking time, but never more than what is still due.
            if ($advanceRequired > 0) {
                return round(min($advanceRequired, $remaining), 2);
            }

            return $remaining;
        }

        // Remaining balance is collected only after the booking is completed/delivered.
        if ($paymentStatus === self::STATUS_ADVANCE_PAID) {
            return $remainingUnlocked ? $remaining : 0.0;
        }

        return $remaining;
    }

    protected function phase(
        string $paymentStatus,
        float $advanceRequired,
        float $remaining,
        bool $remainingUnlocked
    ): string {
        if ($paymentStatus === self::STATUS_SUCCESS || $remaining <= 0) {
            return 'fully_paid';
        }

        if ($paymentStatus === self::STATUS_ADVANCE_PAID) {
            return $remainingUnlocked ? 'remaining_due' : 'advance_paid_waiting';
        }

        // Only "advance due" when advance is a real partial amount (< full remaining).
        if ($advanceRequired > 0 && $advanceRequired + 0.009 < $remaining) {
            return 'advance_due';
        }

        return 'full_due';
    }

    protected function payLabel(
        string $paymentStatus,
        float $advanceRequired,
        float $payableNow,
        string $phase
    ): string {
        if ($phase === 'remaining_due') {
            return 'Pay remaining ₹'.number_format($payableNow, 0);
        }

        if ($phase === 'advance_due') {
            return 'Pay advance ₹'.number_format($payableNow, 0);
        }

        return 'Pay ₹'.number_format($payableNow, 0);
    }

    public function remainingPaymentUnlocked(string $status): bool
    {
        return in_array($status, self::REMAINING_DUE_STATUSES, true);
    }

    protected function isCodConfirmedOrder(Order $order): bool
    {
        return $order->isCod()
            && $order->status !== 'new'
            && ! in_array($order->status, ['cancelled', 'refunded'], true);
    }

    protected function isCodConfirmedCheckout(CheckoutOrder $checkout): bool
    {
        return $checkout->payment_method === 'cod'
            && $checkout->status !== 'new'
            && ! in_array($checkout->status, ['cancelled', 'refunded'], true);
    }
}
