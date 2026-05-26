<?php

return [
    'otp_ttl_minutes' => (int) env('API_OTP_TTL_MINUTES', 5),
    'otp_debug_code' => env('API_OTP_DEBUG_CODE', '1234'),
];
