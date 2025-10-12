<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development origins
        'http://localhost:3000',
        'http://localhost:3001', 
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        
        // Flutter development origins
        'http://localhost',
        'http://127.0.0.1',
        'http://10.0.2.2', // Android emulator
        'http://localhost:8080', // Flutter web dev server

        'http://127.0.0.1:5173',
        'http://localhost:5173',

        // Production origins (из .env)
        ...(env('CORS_ALLOWED_ORIGINS') ? explode(',', env('CORS_ALLOWED_ORIGINS')) : []),
    ],

    'allowed_origins_patterns' => [
        // Паттерны для поддоменов (если нужно)
        ...(env('CORS_ALLOWED_PATTERNS') ? explode(',', env('CORS_ALLOWED_PATTERNS')) : []),
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
