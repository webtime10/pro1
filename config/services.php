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

    /*
    | Читать только через config('services.openai.*') / config('services.gemini.*').
    | При php artisan config:cache вызовы env() вне config/ возвращают null.
    */
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5.4'),
        'extraction_model' => env('OPENAI_EXTRACTION_MODEL', 'gpt-5-mini'),
        'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 16384),
        'ai_article_min_chars' => (int) env('OPENAI_AI_ARTICLE_MIN_CHARS', 2500),
        'rate_limit_retries' => (int) env('OPENAI_RATE_LIMIT_RETRIES', 8),
        'rate_limit_wait_base_sec' => (int) env('OPENAI_RATE_LIMIT_WAIT_BASE_SEC', 10),
    ],

    'gemini' => [
        /** Несколько бесплатных ключей через запятую (ротация как в lara2). */
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'extraction_timeout' => (int) env('GEMINI_EXTRACTION_TIMEOUT', 1800),
        'chat_timeout' => (int) env('GEMINI_CHAT_TIMEOUT', 900),
    ],

    'gemini_pro' => [
        'key' => env('GEMINI_PRO_API_KEY'),
        'model' => env('GEMINI_CREATIVE_MODEL', 'gemini-3.6-flash'),
        'max_output_tokens' => (int) env('GEMINI_PRO_MAX_OUTPUT_TOKENS', 65536),
        'chat_timeout' => (int) env('GEMINI_PRO_CHAT_TIMEOUT', 1800),
    ],

];
