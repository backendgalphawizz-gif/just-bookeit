<?php

namespace App\Services\Vendor;

use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\Refund;
use App\Models\Vendor;
use App\Models\VendorWalletTransaction;
use Illuminate\Support\Facades\DB;

class VendorWalletService
{
    public static function holdDays(): int
    {
        return max(0, (int) config('wallet.hold_days', 15));
    }

    /** @deprecated Use holdDays() — kept for older call sites. */
    public const HOLD_DAYS = 15;

    public function creditFromPayment(Order $order): void
    {
        if (! $order->vendor_id || $order->payment_status !== 'success') {
            return;
        }

        if ($order->wallet_hold_status !== 'none') {
            return;
        }

        if ($this->hasTransaction($order, VendorWalletTransaction::TYPE_PAYMENT_CREDIT)) {
            return;
        }

        $netAmount = $this->vendorNetAmount($order);

        if ($netAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $netAmount): void {
            $vendor = Vendor::query()->lockForUpdate()->find($order->vendor_id);

            if (! $vendor) {
                return;
            }

            $paidAt = $order->paid_at ?? now();

            $order->update([
                'paid_at' => $paidAt,
                'wallet_release_at' => $paidAt->copy()->addDays(self::holdDays()),
                'vendor_net_amount' => $netAmount,
                'vendor_wallet_held_amount' => $netAmount,
                'wallet_hold_status' => 'held',
            ]);

            $vendor->increment('digital_wallet_balance', $netAmount);
            $vendor->refresh();

            $this->recordTransaction(
                vendor: $vendor,
                order: $order,
                type: VendorWalletTransaction::TYPE_PAYMENT_CREDIT,
                wallet: VendorWalletTransaction::WALLET_DIGITAL,
                direction: 'credit',
                amount: $netAmount,
                balanceAfter: (float) $vendor->digital_wallet_balance,
                description: 'Payment credited to digital wallet for '.$order->order_number,
            );
        });
    }

    public function debitForRefund(Refund $refund): void
    {
        $refund->loadMissing('order.vendor');

        $order = $refund->order;

        if (! $order?->vendor_id) {
            return;
        }

        if ($this->hasTransaction($refund, VendorWalletTransaction::TYPE_REFUND_DEBIT)) {
            return;
        }

        if (! in_array($refund->status, Refund::OPEN_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($refund, $order): void {
            $order = Order::query()->lockForUpdate()->find($order->id);
            $vendor = Vendor::query()->lockForUpdate()->find($order->vendor_id);

            if (! $order || ! $vendor) {
                return;
            }

            $debitAmount = min((float) $refund->amount, $this->refundableAmount($order));

            if ($debitAmount <= 0) {
                return;
            }

            if ($order->wallet_hold_status === 'held') {
                $this->applyDigitalDebit($vendor, $order, $refund, $debitAmount);
            } elseif ($order->wallet_hold_status === 'released') {
                $this->applyActualDebit($vendor, $order, $refund, $debitAmount);
            }

            if ($order->fresh()->vendor_wallet_held_amount <= 0) {
                $order->update(['wallet_hold_status' => 'refunded']);
            }
        });
    }

    public function restoreForRejectedRefund(Refund $refund): void
    {
        $refund->loadMissing('order');

        $order = $refund->order;

        if (! $order?->vendor_id) {
            return;
        }

        if ($this->hasTransaction($refund, VendorWalletTransaction::TYPE_REFUND_REVERSAL)) {
            return;
        }

        $originalDebit = VendorWalletTransaction::query()
            ->where('refund_id', $refund->id)
            ->where('type', VendorWalletTransaction::TYPE_REFUND_DEBIT)
            ->first();

        if (! $originalDebit) {
            return;
        }

        DB::transaction(function () use ($refund, $order, $originalDebit): void {
            $order = Order::query()->lockForUpdate()->find($order->id);
            $vendor = Vendor::query()->lockForUpdate()->find($order->vendor_id);

            if (! $order || ! $vendor) {
                return;
            }

            $amount = (float) $originalDebit->amount;

            if ($originalDebit->wallet === VendorWalletTransaction::WALLET_DIGITAL) {
                $vendor->increment('digital_wallet_balance', $amount);
                $order->increment('vendor_wallet_held_amount', $amount);

                if ($order->wallet_hold_status === 'refunded' && $order->vendor_wallet_held_amount > 0) {
                    $order->update(['wallet_hold_status' => 'held']);
                }

                $vendor->refresh();

                $this->recordTransaction(
                    vendor: $vendor,
                    order: $order,
                    type: VendorWalletTransaction::TYPE_REFUND_REVERSAL,
                    wallet: VendorWalletTransaction::WALLET_DIGITAL,
                    direction: 'credit',
                    amount: $amount,
                    balanceAfter: (float) $vendor->digital_wallet_balance,
                    description: 'Refund request rejected — amount restored for '.$order->order_number,
                    refund: $refund,
                );
            } else {
                $vendor->increment('wallet_balance', $amount);
                $vendor->refresh();

                $this->recordTransaction(
                    vendor: $vendor,
                    order: $order,
                    type: VendorWalletTransaction::TYPE_REFUND_REVERSAL,
                    wallet: VendorWalletTransaction::WALLET_ACTUAL,
                    direction: 'credit',
                    amount: $amount,
                    balanceAfter: (float) $vendor->wallet_balance,
                    description: 'Refund request rejected — amount restored for '.$order->order_number,
                    refund: $refund,
                );

                if ($order->wallet_hold_status === 'refunded') {
                    $order->update(['wallet_hold_status' => 'released']);
                }
            }
        });
    }

    public function releaseExpiredHolds(): int
    {
        $released = 0;

        Order::query()
            ->where('wallet_hold_status', 'held')
            ->where('vendor_wallet_held_amount', '>', 0)
            ->whereNotNull('wallet_release_at')
            ->where('wallet_release_at', '<=', now())
            ->whereDoesntHave('refund', fn ($q) => $q->whereIn('status', Refund::OPEN_STATUSES))
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$released): void {
                foreach ($orders as $order) {
                    if ($this->releaseOrderHold($order)) {
                        $released++;
                    }
                }
            });

        return $released;
    }

    public function vendorNetAmount(Order $order): float
    {
        $gross = $order->grandTotal();
        $commissionPercent = (float) PlatformSetting::get('global_commission_percent', 0);
        $commission = round($gross * ($commissionPercent / 100), 2);

        return max(0, round($gross - $commission, 2));
    }

    protected function releaseOrderHold(Order $order): bool
    {
        return (bool) DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->find($order->id);

            if (! $order?->vendor_id || $order->wallet_hold_status !== 'held') {
                return false;
            }

            if ($order->vendor_wallet_held_amount <= 0) {
                return false;
            }

            if ($order->wallet_release_at && $order->wallet_release_at->isFuture()) {
                return false;
            }

            if ($order->refund()->whereIn('status', Refund::OPEN_STATUSES)->exists()) {
                return false;
            }

            $vendor = Vendor::query()->lockForUpdate()->find($order->vendor_id);

            if (! $vendor) {
                return false;
            }

            $amount = (float) $order->vendor_wallet_held_amount;

            $vendor->decrement('digital_wallet_balance', $amount);
            $vendor->increment('wallet_balance', $amount);
            $vendor->refresh();

            $order->update([
                'vendor_wallet_held_amount' => 0,
                'wallet_hold_status' => 'released',
                'wallet_settled_at' => now(),
            ]);

            $this->recordTransaction(
                vendor: $vendor,
                order: $order,
                type: VendorWalletTransaction::TYPE_HOLD_RELEASE,
                wallet: VendorWalletTransaction::WALLET_DIGITAL,
                direction: 'debit',
                amount: $amount,
                balanceAfter: (float) $vendor->digital_wallet_balance,
                description: 'Hold period ended — moved to actual wallet for '.$order->order_number,
            );

            $this->recordTransaction(
                vendor: $vendor,
                order: $order,
                type: VendorWalletTransaction::TYPE_HOLD_RELEASE,
                wallet: VendorWalletTransaction::WALLET_ACTUAL,
                direction: 'credit',
                amount: $amount,
                balanceAfter: (float) $vendor->wallet_balance,
                description: 'Hold period ended — funds available for withdrawal for '.$order->order_number,
            );

            return true;
        });
    }

    protected function refundableAmount(Order $order): float
    {
        if ($order->wallet_hold_status === 'held') {
            return (float) $order->vendor_wallet_held_amount;
        }

        if ($order->wallet_hold_status === 'released') {
            return max(0, (float) $order->vendor_net_amount - $this->refundedAmount($order));
        }

        return 0;
    }

    protected function refundedAmount(Order $order): float
    {
        return (float) VendorWalletTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', VendorWalletTransaction::TYPE_REFUND_DEBIT)
            ->sum('amount');
    }

    protected function applyDigitalDebit(Vendor $vendor, Order $order, Refund $refund, float $debitAmount): void
    {
        $vendor->decrement('digital_wallet_balance', $debitAmount);
        $order->decrement('vendor_wallet_held_amount', $debitAmount);
        $vendor->refresh();

        $this->recordTransaction(
            vendor: $vendor,
            order: $order,
            type: VendorWalletTransaction::TYPE_REFUND_DEBIT,
            wallet: VendorWalletTransaction::WALLET_DIGITAL,
            direction: 'debit',
            amount: $debitAmount,
            balanceAfter: (float) $vendor->digital_wallet_balance,
            description: 'Refund request — deducted from digital wallet for '.$order->order_number,
            refund: $refund,
        );
    }

    protected function applyActualDebit(Vendor $vendor, Order $order, Refund $refund, float $debitAmount): void
    {
        $vendor->decrement('wallet_balance', $debitAmount);
        $vendor->refresh();

        $this->recordTransaction(
            vendor: $vendor,
            order: $order,
            type: VendorWalletTransaction::TYPE_REFUND_DEBIT,
            wallet: VendorWalletTransaction::WALLET_ACTUAL,
            direction: 'debit',
            amount: $debitAmount,
            balanceAfter: (float) $vendor->wallet_balance,
            description: 'Refund request — deducted from actual wallet for '.$order->order_number,
            refund: $refund,
        );
    }

    protected function hasTransaction(Order|Refund $subject, string $type): bool
    {
        $query = VendorWalletTransaction::query()->where('type', $type);

        if ($subject instanceof Order) {
            return $query->where('order_id', $subject->id)->exists();
        }

        return $query->where('refund_id', $subject->id)->exists();
    }

    protected function recordTransaction(
        Vendor $vendor,
        Order $order,
        string $type,
        string $wallet,
        string $direction,
        float $amount,
        float $balanceAfter,
        string $description,
        ?Refund $refund = null,
    ): VendorWalletTransaction {
        return VendorWalletTransaction::query()->create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'refund_id' => $refund?->id,
            'type' => $type,
            'wallet' => $wallet,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);
    }
}
