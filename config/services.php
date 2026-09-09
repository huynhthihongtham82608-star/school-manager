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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
        'model_primary' => env('GEMINI_MODEL_PRIMARY', env('GEMINI_MODEL', 'gemini-flash-lite-latest')),
        'model_fallback' => env('GEMINI_MODEL_FALLBACK', 'gemini-3.7-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'connect_timeout' => env('GEMINI_CONNECT_TIMEOUT', 10),
        'timeout' => env('GEMINI_TIMEOUT', 30),
        'ca_bundle' => env('GEMINI_CA_BUNDLE'),
    ],

    'chatbot' => [
        'avatar_path' => env('CHATBOT_AVATAR_PATH', 'images/chatbot-avatar.png'),
        'demo_mode' => env('CHATBOT_DEMO_MODE', false),
    ],

];
