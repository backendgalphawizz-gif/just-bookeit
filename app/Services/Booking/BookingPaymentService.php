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
        $advanceRequired = $this->requiredAdvanceForOrder($order, $pricing);
        $amountPaid = round((float) ($order->amount_paid ?? 0), 2);
        $remaining = round(max(0, $total - $amountPaid), 2);
        $remainingUnlocked = $this->remainingPaymentUnlocked($order->status);
        $payableNow = $this->payableNow(
            $order->payment_status,
            $advanceRequired,
            $total,
            $amountPaid,
            $remainingUnlocked
        );
        $phase = $this->phase($order->payment_status, $advanceRequired, $remaining, $remainingUnlocked);

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
            'is_fully_paid' => in_array($order->payment_status, [self::STATUS_SUCCESS], true) || $remaining <= 0.0,
            'can_pay' => $payableNow > 0 && ! in_array($order->payment_status, [self::STATUS_SUCCESS, 'refunded'], true),
            'pay_label' => $this->payLabel($order->payment_status, $advanceRequired, $payableNow, $phase),
        ];
    }

    /** @return array<string, mixed> */
    public function summaryForCheckout(CheckoutOrder $checkout): array
    {
        $checkout->loadMissing(['subOrders.orderItems.portfolioItem', 'subOrders.portfolioItem']);
        $total = round((float) $checkout->grand_total, 2);
        $advanceRequired = $this->requiredAdvanceForCheckout($checkout);
        $amountPaid = round((float) ($checkout->amount_paid ?? 0), 2);
        $remaining = round(max(0, $total - $amountPaid), 2);
        $remainingUnlocked = $checkout->subOrders->contains(
            fn (Order $sub) => $this->remainingPaymentUnlocked($sub->status)
        ) || $this->remainingPaymentUnlocked($checkout->status);
        $payableNow = $this->payableNow(
            $checkout->payment_status,
            $advanceRequired,
            $total,
            $amountPaid,
            $remainingUnlocked
        );
        $phase = $this->phase($checkout->payment_status, $advanceRequired, $remaining, $remainingUnlocked);

        return [
            'subtotal' => round((float) $checkout->amount, 2),
            'shipping_fee' => round((float) $checkout->delivery_fee, 2),
            'tax_amount' => round((float) $checkout->tax_amount, 2),
            'tax_percent' => BookingPricingService::gstPercent(),
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
            'is_fully_paid' => in_array($checkout->payment_status, [self::STATUS_SUCCESS], true) || $remaining <= 0.0,
            'can_pay' => $payableNow > 0 && ! in_array($checkout->payment_status, [self::STATUS_SUCCESS, 'refunded', 'partially_refunded'], true),
            'pay_label' => $this->payLabel($checkout->payment_status, $advanceRequired, $payableNow, $phase),
        ];
    }

    public function payOrder(Order $order, string $paymentMethod): Order
    {
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
        $newPaid = round((float) ($order->amount_paid ?? 0) + $payableNow, 2);
        $nextStatus = $newPaid + 0.009 >= $total ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;

        $order->update([
            'advance_amount' => $summary['advance_amount'],
            'amount_paid' => $newPaid,
            'payment_status' => $nextStatus,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
            'status' => $order->status === 'new' ? 'pending_acceptance' : $order->status,
        ]);

        $order = $order->fresh(['vendor', 'category', 'orderItems.portfolioItem', 'portfolioItem']);

        if ($nextStatus === self::STATUS_SUCCESS) {
            $this->wallet->creditFromPayment($order);
        }

        return $order;
    }

    public function payCheckout(CheckoutOrder $checkout, string $paymentMethod): CheckoutOrder
    {
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
            $newPaid = round((float) ($checkout->amount_paid ?? 0) + $payableNow, 2);
            $nextStatus = $newPaid + 0.009 >= $total ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;
            $checkoutTotal = max(0.01, $total);

            $checkout->update([
                'advance_amount' => $summary['advance_amount'],
                'amount_paid' => $newPaid,
                'payment_status' => $nextStatus,
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
                'status' => $checkout->status === 'new' ? 'pending_acceptance' : $checkout->status,
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

                $subPaid = round((float) ($subOrder->amount_paid ?? 0) + max(0, $subPay), 2);
                $subStatus = $subPaid + 0.009 >= $subTotal ? self::STATUS_SUCCESS : self::STATUS_ADVANCE_PAID;
                if ($nextStatus === self::STATUS_SUCCESS) {
                    $subStatus = self::STATUS_SUCCESS;
                    $subPaid = max($subPaid, $subTotal);
                }

                $subOrder->update([
                    'advance_amount' => $subSummary['advance_amount'],
                    'amount_paid' => $subPaid,
                    'payment_status' => $subStatus,
                    'payment_method' => $paymentMethod,
                    'paid_at' => now(),
                    'status' => $subOrder->status === 'new' ? 'pending_acceptance' : $subOrder->status,
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
            // Booking-time charge: advance only when set, otherwise full booking amount.
            return $advanceRequired > 0 ? round($advanceRequired, 2) : $remaining;
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

        return $advanceRequired > 0 ? 'advance_due' : 'full_due';
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

        if ($phase === 'advance_due' || ($paymentStatus === self::STATUS_PENDING && $advanceRequired > 0)) {
            return 'Pay advance ₹'.number_format($payableNow, 0);
        }

        return 'Pay ₹'.number_format($payableNow, 0);
    }

    public function remainingPaymentUnlocked(string $status): bool
    {
        return in_array($status, self::REMAINING_DUE_STATUSES, true);
    }
}
