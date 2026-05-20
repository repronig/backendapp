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
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'api_key' => env('ONESIGNAL_API_KEY'),
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'termii'),
        'termii' => [
            'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
            'api_key' => env('TERMII_API_KEY'),
            'sender_id' => env('TERMII_SENDER_ID', 'REPRONIG'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA (v2 checkbox) — registration spam protection
    |--------------------------------------------------------------------------
    |
    | When "secret" is non-empty, member and institution registration require
    | recaptcha_token only from the web app (browser Origin/Referer or X-Repronig-Client: web).
    | Native mobile apps omit reCAPTCHA by design and need no app update.
    |
    */
    'recaptcha' => [
        'secret' => env('RECAPTCHA_SECRET_KEY'),
        /** Public v2 checkbox site key; exposed in GET /platform-settings when secret is set. */
        'site_key' => env('RECAPTCHA_SITE_KEY'),
    ],

];
