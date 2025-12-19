<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development origins
        'http://localhost:3000',
        'http://localhost:3001', 
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:5173',
        'http://localhost:5173',
        'http://127.0.0.1:8100',
        'http://localhost:8100',

        // Flutter development origins
        'http://localhost',
        'http://127.0.0.1',
        'http://10.0.2.2', // Android emulator
        'http://localhost:8080', // Flutter web dev server

        // Production origins (из .env)
        ...(env('CORS_ALLOWED_ORIGINS') ? explode(',', env('CORS_ALLOWED_ORIGINS')) : []),
    ],

    'allowed_origins_patterns' => [
        // Паттерны для поддоменов (если нужно)
        ...(env('CORS_ALLOWED_PATTERNS') ? explode(',', env('CORS_ALLOWED_PATTERNS')) : []),
    ],

    'allowed_headers' => ['*'],

    // Exposed headers для cookies и CSRF токенов
    'exposed_headers' => [
        'Set-Cookie',
        'X-XSRF-TOKEN',
    ],

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
