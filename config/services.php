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
    | App\Services\Ai\NutritionAi: text through Claude (anthropic-ai/sdk), photos through OpenAI (openai-php/client).
    */
    'ai' => [
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        'openai_key' => env('OPENAI_API_KEY'),
        'plan_model' => env('AI_DIET_MODEL', 'claude-opus-5-5'),
        'dish_model' => env('AI_DISH_MODEL', env('AI_DIET_MODEL', 'claude-opus-5-5')),
        'effort' => env('AI_EFFORT', 'medium'),
        'image_model' => env('AI_IMAGE_MODEL', 'gpt-image-1'),
        'images_enabled' => (bool) env('AI_IMAGES_ENABLED', true),
        'new_dishes_per_plan' => (int) env('AI_NEW_DISHES_PER_PLAN', 6),
        'publish_new_dishes' => (bool) env('AI_PUBLISH_NEW_DISHES', true),
    ],

];
