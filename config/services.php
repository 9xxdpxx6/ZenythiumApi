<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'fcm' => [
        'use_v1_api' => env('FCM_USE_V1_API', true),
        'server_key' => env('FCM_SERVER_KEY'),
        'sender_id' => env('FCM_SENDER_ID'),
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH', storage_path('app/private/firebase-service-account.json')),
    ],

    /*
     * Yandex SmartCaptcha (регистрация).
     * По умолчанию на local капча не обязательна; на production/staging — обязательна.
     * Переопределение: YANDEX_SMARTCAPTCHA_REQUIRED=true|false
     */
    'yandex_smartcaptcha' => [
        'server_key' => env('YANDEX_SMARTCAPTCHA_SERVER_KEY'),
        'required' => (static function (): bool {
            $v = env('YANDEX_SMARTCAPTCHA_REQUIRED');
            if ($v !== null && $v !== '') {
                return filter_var($v, FILTER_VALIDATE_BOOLEAN);
            }

            return env('APP_ENV', 'production') !== 'local';
        })(),
    ],

];
