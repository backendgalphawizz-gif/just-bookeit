<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vendor wallet hold period (days)
    |--------------------------------------------------------------------------
    |
    | Customer payments first credit the vendor digital wallet. After this many
    | days (from paid_at), `php artisan wallet:release-holds` moves the funds
    | into the actual wallet. Override with VENDOR_WALLET_HOLD_DAYS in .env.
    |
    */
    'hold_days' => (int) env('VENDOR_WALLET_HOLD_DAYS', 15),

];
