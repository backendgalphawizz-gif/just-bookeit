<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\Driver;
use App\Models\VendorPayout;

class CodeGenerator
{
    public static function customerCode(): string
    {
        return self::uniqueCode(
            'CUS', 5,
            fn () => (int) (Customer::query()->max('id') ?? 0),
            fn ($code) => Customer::query()->where('customer_code', $code)->exists()
        );
    }

    public static function vendorCode(): string
    {
        return self::uniqueCode(
            'VEN', 5,
            fn () => (int) (Vendor::query()->max('id') ?? 0),
            fn ($code) => Vendor::query()->where('vendor_code', $code)->exists()
        );
    }

    public static function orderNumber(): string
    {
        $prefix = 'JB'.now()->format('ym');

        return self::uniqueCode(
            $prefix, 5,
            fn () => max(
                (int) (Order::query()->max('id') ?? 0),
                (int) (\App\Models\CheckoutOrder::query()->max('id') ?? 0)
            ),
            fn ($code) => Order::query()->where('order_number', $code)->exists()
                || \App\Models\CheckoutOrder::query()->where('order_number', $code)->exists()
        );
    }

    public static function subOrderNumber(string $parentNumber, int $sequence): string
    {
        return $parentNumber.'-V'.$sequence;
    }

    public static function payoutCode(): string
    {
        return self::uniqueCode(
            'PAY', 5,
            fn () => (int) (VendorPayout::query()->max('id') ?? 0),
            fn ($code) => VendorPayout::query()->where('payout_code', $code)->exists()
        );
    }

    public static function driverCode(): string
    {
        return self::uniqueCode(
            'DRV', 5,
            fn () => (int) (Driver::query()->max('id') ?? 0),
            fn ($code) => Driver::query()->where('driver_code', $code)->exists()
        );
    }

    /**
     * Generate the next available sequential code.
     * Starts from max(id)+1 as a hint, then increments until a non-colliding code is found.
     *
     * @param  callable(): int       $hintFn    Returns the starting sequence hint.
     * @param  callable(string): bool $existsFn  Returns true if the code is already taken.
     */
    private static function uniqueCode(string $prefix, int $pad, callable $hintFn, callable $existsFn): string
    {
        $n = max(1, $hintFn() + 1);

        do {
            $code = $prefix.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
            $n++;
        } while ($existsFn($code));

        return $code;
    }

    public static function driverTransactionCode(): string
    {
        $next = (\App\Models\DriverWalletTransaction::query()->max('id') ?? 0) + 1;

        return 'TXN'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
