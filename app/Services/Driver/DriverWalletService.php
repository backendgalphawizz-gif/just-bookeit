<?php

namespace App\Services\Driver;

use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverWalletService
{
    public function payoutAmountFor(Order $order): float
    {
        $configured = config('api.driver_delivery_payout');

        if ($configured !== null) {
            return round((float) $configured, 2);
        }

        return round((float) ($order->delivery_fee ?? 0), 2);
    }

    public function creditDeliveryEarning(Order $order, Driver $driver): DriverWalletTransaction
    {
        if ($order->driver_id !== $driver->id) {
            throw new InvalidArgumentException('Driver does not own this delivery.');
        }

        $amount = (float) ($order->driver_earning ?? $this->payoutAmountFor($order));

        if ($amount <= 0) {
            throw new InvalidArgumentException('Delivery payout amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $driver, $amount) {
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            if ($order->driver_earning !== null) {
                $existing = DriverWalletTransaction::query()
                    ->where('order_id', $order->id)
                    ->where('type', DriverWalletTransaction::TYPE_DELIVERY_CREDIT)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $balanceAfter = round((float) $lockedDriver->wallet_balance + $amount, 2);

            $lockedDriver->update([
                'wallet_balance' => $balanceAfter,
                'total_earnings' => round((float) $lockedDriver->total_earnings + $amount, 2),
            ]);

            $order->update(['driver_earning' => $amount]);

            return DriverWalletTransaction::query()->create([
                'transaction_code' => CodeGenerator::driverTransactionCode(),
                'driver_id' => $lockedDriver->id,
                'order_id' => $order->id,
                'type' => DriverWalletTransaction::TYPE_DELIVERY_CREDIT,
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'description' => 'Delivery completed for order '.$order->order_number,
            ]);
        });
    }

    public function withdraw(Driver $driver, float $amount): DriverWalletTransaction
    {
        $minAmount = (float) config('api.driver_min_withdrawal', 100);

        if ($amount < $minAmount) {
            throw new InvalidArgumentException('Minimum withdrawal amount is ₹'.number_format($minAmount, 0).'.');
        }

        return DB::transaction(function () use ($driver, $amount) {
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            if ((float) $lockedDriver->wallet_balance < $amount) {
                throw new InvalidArgumentException('Insufficient wallet balance.');
            }

            $balanceAfter = round((float) $lockedDriver->wallet_balance - $amount, 2);

            $lockedDriver->update(['wallet_balance' => $balanceAfter]);

            return DriverWalletTransaction::query()->create([
                'transaction_code' => CodeGenerator::driverTransactionCode(),
                'driver_id' => $lockedDriver->id,
                'type' => DriverWalletTransaction::TYPE_WITHDRAWAL_DEBIT,
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'description' => 'Withdrawal to bank account',
            ]);
        });
    }
}
