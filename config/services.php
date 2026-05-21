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

    'mistral' => [
    'api_key' => env('MISTRAL_API_KEY'),
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

    'monetbil' => [
        'enabled' => env('MONETBIL_ENABLED', false),
        'base_url' => env('MONETBIL_BASE_URL', 'https://api.monetbil.com'),
        'version' => env('MONETBIL_VERSION', 'v2.1'),
        'service_key' => env('MONETBIL_SERVICE_KEY'),
        'service_secret' => env('MONETBIL_SERVICE_SECRET'),
        'country' => env('MONETBIL_COUNTRY', 'CM'),
        'currency' => env('MONETBIL_CURRENCY', 'XAF'),
        'timeout' => env('MONETBIL_TIMEOUT', 15),
        'retry_times' => env('MONETBIL_RETRY_TIMES', 2),
        'retry_sleep_ms' => env('MONETBIL_RETRY_SLEEP_MS', 300),
        'cameroon_prefixes' => [
            'mtn' => ['650-654', '670-679', '680-683'],
            'orange' => ['655-659', '685-689', '690-699'],
        ],

        'number_validation' => [
            'labels' => [
                'card' => 'Orange Money',
                'mobile_money' => 'MTN Momo',
                'mtn' => 'MTN Momo',
                'orange' => 'Orange Money',
            ],
        ],
    ],

];
