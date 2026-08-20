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

    // 第三方 OAuth 登录：GitHub
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', env('APP_URL') . '/oauth/callback/github'),
    ],

    // 第三方 OAuth 登录：Google
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/oauth/callback/google'),
    ],

    // 第三方 OAuth 登录：Microsoft（Azure AD v2.0，由 socialiteproviders/microsoft 提供）
    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL') . '/oauth/callback/microsoft'),
        // Azure 租户：与 .env 中的 MICROSOFT_TENANT_ID 保持一致（.env 里既有 MICROSOFT_TENANT_ID 也有 MICROSOFT_TENANT）
        'tenant' => env('MICROSOFT_TENANT_ID', env('MICROSOFT_TENANT', 'common')),
        'login_url' => 'https://login.microsoftonline.com/' . env('MICROSOFT_TENANT_ID', env('MICROSOFT_TENANT', 'common')),
    ],

];
