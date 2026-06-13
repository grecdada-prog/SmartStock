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

    'monetbil' => [

        'shop' => [
            'service_key'    => env('MONETBIL_SHOP_SERVICE_KEY'),
            'service_secret' => env('MONETBIL_SHOP_SERVICE_SECRET'),
        ],

        'token' => [
            'service_key'    => env('MONETBIL_TOKEN_SERVICE_KEY'),
            'service_secret' => env('MONETBIL_TOKEN_SERVICE_SECRET'),
        ],
        'base_url' => env('MONETBIL_BASE_URL', 'https://api.monetbil.com/payment/v1'),
        'country' => env('MONETBIL_COUNTRY', 'CM'),
        'currency' => env('MONETBIL_CURRENCY', 'XAF'),
        'verify_ssl' => env('MONETBIL_VERIFY_SSL', true),
        'send_operator' => env('MONETBIL_SEND_OPERATOR', false),
        'operators' => [
            'mtn_momo' => env('MONETBIL_OPERATOR_MTN', 'CM_MTNMOBILEMONEY'),
            'orange_money' => env('MONETBIL_OPERATOR_ORANGE', 'CM_ORANGEMONEY'),
        ],
        'number_validation' => [
            'labels' => [
                'mtn_momo' => 'MTN MoMo',
                'orange_money' => 'Orange Money',
            ],
            'prefixes' => [
                'mtn_momo' => ['650', '651', '652', '653', '654', '67', '680', '681', '682', '683'],
                'orange_money' => ['640', '655', '656', '657', '658', '659', '686', '687', '688', '689', '69'],
            ],
        ],
        'fake_mode' => env('PAYMENT_FAKE_MODE', false),
        'fake_result' => env('PAYMENT_FAKE_RESULT', 'success'),
    ],

    'avlytext' => [
        'api_key' => env('AVLYTEXT_API_KEY'),
        'base_url' => env('AVLYTEXT_BASE_URL', 'https://api.avlytext.com/v1'),
        'sender' => env('AVLYTEXT_SENDER', 'Smart City'),
        'sender_mtn' => env('AVLYTEXT_SENDER_MTN'),
        'verify_ssl' => env('AVLYTEXT_VERIFY_SSL', true),
    ],

    'socadel_token' => [
        'api_key' => env('SOCADEL_TOKEN_API_KEY'),
        'base_url' => env('SOCADEL_TOKEN_BASE_URL', 'https://www.smartcitydouala.com/api/v1'),
        'endpoint' => env('SOCADEL_TOKEN_ENDPOINT', '/token/socadel'),
        'verify_ssl' => env('SOCADEL_TOKEN_VERIFY_SSL', true),
        'fake_mode' => env('SOCADEL_TOKEN_FAKE_MODE', in_array(env('APP_ENV'), ['local', 'testing'], true)),
    ],

];
