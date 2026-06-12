<?php

return [
    'otp_ttl_minutes' => (int) env('API_OTP_TTL_MINUTES', 5),

    'driver_delivery_payout' => env('DRIVER_DELIVERY_PAYOUT') !== null
        ? (float) env('DRIVER_DELIVERY_PAYOUT')
        : null,

    'driver_min_withdrawal' => (float) env('DRIVER_MIN_WITHDRAWAL', 100),
];
