<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (HTTP v1)
    |--------------------------------------------------------------------------
    |
    | Service account JSON path — defaults to config/firebase-service-account.json
    | Project ID is read from that file when FIREBASE_PROJECT_ID is not set.
    |
    */

    'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH', config_path('firebase-service-account.json')),

    'project_id' => env('FIREBASE_PROJECT_ID'),

    'access_token_cache_key' => 'firebase:fcm:access_token',

    'access_token_ttl_seconds' => 3500,

];
