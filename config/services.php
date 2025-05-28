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

    // config/services.php - konfigurasi untuk OpenAI API
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'), // Opsional
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo-instruct'), // Untuk Completion API
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'), // Untuk Chat API
        'use_chat_api' => env('OPENAI_USE_CHAT_API', true), // true = Chat API, false = Completion API
        'timeout' => env('OPENAI_TIMEOUT', 60), // Timeout dalam detik
    ],

    'ksp_api' => [
        'enabled' => env('KSP_API_ENABLED', true),
        'url' => env('KSP_API_URL', 'https://layanan-api.ksp.go.id/index.php/login'),
        'key' => env('KSP_API_KEY', 'e7f0s9Cc9feBf61d49i3Kz5'),
        'timeout' => env('KSP_API_TIMEOUT', 30),
        'verify_ssl' => env('KSP_API_VERIFY_SSL', false),
        'debug_enabled' => env('KSP_API_DEBUG', false),
        'log_requests' => env('KSP_API_LOG_REQUESTS', true),
    ],

];
