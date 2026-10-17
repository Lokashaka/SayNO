<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'safe_browsing' => [
        'key' => env('SAFE_BROWSING_KEY'),
        'endpoint' => 'https://safebrowsing.googleapis.com/v4/threatMatches:find',
        'cache_minutes' => 60,
    ],

    'rdap' => [
        'endpoint' => 'https://rdap.org/domain/',
        'cache_hours' => 24,
    ],

    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'phone_number_id' => env('META_PHONE_NUMBER_ID'),
        'waba_id' => env('META_WABA_ID'),
        'graph_version' => env('META_GRAPH_VERSION', 'v25.0'),
        'graph_url' => 'https://graph.facebook.com',
        // Media hanya diunduh dari host milik Meta yang dikembalikan Graph API.
        'media_hosts' => ['fbsbx.com', 'whatsapp.net', 'facebook.com', 'fbcdn.net'],
    ],

];
