<?php

return [
    'otp_ttl_minutes' => (int) env('API_OTP_TTL_MINUTES', 5),
    'otp_debug_code' => env('API_OTP_DEBUG_CODE', '1234'),
    /*
     * When true, Send OTP uses the fixed debug code and returns it in the JSON
     * response (`otp` + `debug_otp`) for Postman / QA. Off in production.
     */
    'otp_test_mode' => filter_var(
        env('API_OTP_TEST_MODE', env('APP_ENV') !== 'production'),
        FILTER_VALIDATE_BOOLEAN
    ),
];
