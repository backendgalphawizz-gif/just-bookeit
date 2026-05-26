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
        $next = (Customer::query()->max('id') ?? 0) + 1;

        return 'CUS'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function vendorCode(): string
    {
        $next = (Vendor::query()->max('id') ?? 0) + 1;

        return 'VEN'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function orderNumber(): string
    {
        $next = (Order::query()->max('id') ?? 0) + 1;

        return 'JB'.now()->format('ym').str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function payoutCode(): string
    {
        $next = (VendorPayout::query()->max('id') ?? 0) + 1;

        return 'PAY'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function driverCode(): string
    {
        $next = (Driver::query()->max('id') ?? 0) + 1;

        return 'DRV'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
