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
    | App\Services\Ai\NutritionAi. Text (meal plans, recipes): OpenAI by default, or Claude with
    | AI_TEXT_PROVIDER=anthropic. Photos: always OpenAI (openai-php/client).
    */
    'ai' => [
        'text_provider' => env('AI_TEXT_PROVIDER', 'openai'),   // openai | anthropic
        'openai_key' => env('OPENAI_API_KEY'),
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        'plan_model' => env('AI_DIET_MODEL', 'gpt-4.1-mini'),
        'dish_model' => env('AI_DISH_MODEL', env('AI_DIET_MODEL', 'gpt-4.1-mini')),
        'effort' => env('AI_EFFORT', 'medium'),   // Claude only
        'image_model' => env('AI_IMAGE_MODEL', 'gpt-image-1'),
        'image_quality' => env('AI_IMAGE_QUALITY', 'medium'),   // gpt-image-*: low | medium | high
        'images_enabled' => (bool) env('AI_IMAGES_ENABLED', true),
        'new_dishes_per_plan' => (int) env('AI_NEW_DISHES_PER_PLAN', 6),
        'publish_new_dishes' => (bool) env('AI_PUBLISH_NEW_DISHES', true),
    ],

    // Search Console / Bing Webmaster verification codes (meta tags), see partials/seo.
    'seo' => [
        'google_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'bing_verification' => env('BING_SITE_VERIFICATION'),
    ],

    // Sign in with Google (laravel/socialite). Redirect URI to register in Google Cloud Console: {APP_URL}/auth/google/callback
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

];
