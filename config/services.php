<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'api_base_url' => env('STRIPE_API_BASE_URL', 'https://api.stripe.com'),
        'catalog_allowed_slugs' => array_values(array_filter(array_map('trim', explode(',', (string) env('STRIPE_CATALOG_ALLOWED_SLUGS', 'growth,pro'))))),
        'checkout_success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL', env('APP_URL') . '/billing/success'),
        'checkout_cancel_url' => env('STRIPE_CHECKOUT_CANCEL_URL', env('APP_URL') . '/billing/cancel'),
        'portal_return_url' => env('STRIPE_BILLING_PORTAL_RETURN_URL', env('APP_URL') . '/manage/forms'),
        'plan_map' => [
            'growth' => [
                'price_id' => env('STRIPE_GROWTH_PRICE_ID'),
                'product_id' => env('STRIPE_GROWTH_PRODUCT_ID'),
            ],
            'pro' => [
                'price_id' => env('STRIPE_PRO_PRICE_ID'),
                'product_id' => env('STRIPE_PRO_PRODUCT_ID'),
            ],
        ],
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance_seconds' => (int) env('STRIPE_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],

];
