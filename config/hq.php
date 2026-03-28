<?php

return [
    'enabled' => (bool) env('CAPTURE_HQ_ENABLED', true),

    'credentials' => [
        'app_name' => env('HQ_API_V1_APP_NAME'),
        'app_id' => env('HQ_API_V1_APP_ID'),
        'app_key' => env('HQ_API_V1_APP_KEY'),
        'app_secret' => env('HQ_API_V1_APP_SECRET'),
        'signature_tolerance_seconds' => (int) env('HQ_API_V1_SIGNATURE_TOLERANCE_SECONDS', 300),
    ],

    'sync' => [
        'subscription_url' => env('SUBSCRIPTION_HQ_SYNC_URL'),
        'feedback_url' => env('FEEDBACK_HQ_SYNC_URL'),
        'analytics_url' => env('ANALYTICS_HQ_SYNC_URL'),
    ],

    'http' => [
        'timeout_seconds' => (int) env('CAPTURE_HQ_TIMEOUT_SECONDS', 5),
        'retry_times' => (int) env('CAPTURE_HQ_RETRY_TIMES', 1),
        'retry_sleep_milliseconds' => (int) env('CAPTURE_HQ_RETRY_SLEEP_MS', 100),
    ],
];
