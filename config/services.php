<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
        // TMDB configuration
    'tmdb' => [
        'api_key' => env('TMDB_API_KEY'),
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'image_url' => env('TMDB_IMAGE_URL', 'https://image.tmdb.org/t/p'),
        'timeout' => env('TMDB_TIMEOUT', 30),
        'language' => env('TMDB_LANGUAGE', 'en-US'),
        'region' => env('TMDB_REGION', 'US'),
        'include_adult' => env('TMDB_INCLUDE_ADULT', false),
        'cache_ttl' => env('TMDB_CACHE_TTL', 3600),
    ],
    
    // Telegram Bot API configuration
    'telegram_bot' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
    ],
];
