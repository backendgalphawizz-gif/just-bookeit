<?php

return [
    'otp_ttl_minutes' => (int) env('API_OTP_TTL_MINUTES', 5),
    'otp_debug_code' => env('API_OTP_DEBUG_CODE', '1234'),
    /*
     * When true, Send OTP uses API_OTP_DEBUG_CODE and returns `otp` in the JSON body.
     * On live servers (APP_ENV=production) this is OFF unless you set API_OTP_TEST_MODE=true
     * in .env and run: php artisan config:clear
     */
    'otp_test_mode' => env('API_OTP_TEST_MODE') !== null && env('API_OTP_TEST_MODE') !== ''
        ? filter_var(env('API_OTP_TEST_MODE'), FILTER_VALIDATE_BOOLEAN)
        : ! in_array(env('APP_ENV', 'production'), ['production'], true),
];
