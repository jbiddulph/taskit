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

    'google_analytics' => [
        'tracking_id' => env('GOOGLE_ANALYTICS_TRACKING_ID'),
    ],

    'cloudflare' => [
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API'),
    ],

    'heroku' => [
        'api_key' => env('HEROKU_API'),
        'app_name' => env('HEROKU_APP_NAME', 'taskit'),
    ],

    'n8n' => [
        'meeting_notes_webhook_url' => env('N8N_MEETING_NOTES_WEBHOOK_URL'),
        'document_extraction_webhook_url' => env('N8N_DOCUMENT_EXTRACTION_WEBHOOK_URL'),
        // Optional: notify n8n when a task is created. Never block task creation on this call.
        'new_task_webhook_url' => env('N8N_NEW_TASK_WEBHOOK_URL', 'https://n8njb-6378e565ae08.herokuapp.com/webhook/new-task'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'mapbox' => [
        'access_token' => env('MAPBOX_ACCESS_TOKEN', env('VITE_MAPBOX_ACCESS_TOKEN')),
    ],

    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
    ],

    // Platform-level key for the ZapProperty portal (/api/v1/zapproperty/*).
    // Generate with `php artisan zapproperty:key`. Unset = portal disabled.
    'zapproperty' => [
        'api_key' => env('ZAPPROPERTY_API_KEY'),
    ],

];
