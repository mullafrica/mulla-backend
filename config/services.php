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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'customerio' => [
        'site_id'  => env('CUSTOMERIO_SITE_ID'),
        'api_key'  => env('CUSTOMERIO_API_KEY'),
    ],

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'webhook_url_dev' => env('DISCORD_WEBHOOK_URL_DEV'),
        'rate_limit' => [
            'max_requests_per_minute' => env('DISCORD_RATE_LIMIT_MAX', 30),
            'batch_size' => env('DISCORD_BATCH_SIZE', 10),
            'batch_timeout_seconds' => env('DISCORD_BATCH_TIMEOUT', 5),
        ],
    ],
];
