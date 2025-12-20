<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    'path' => env('SESSION_PATH', '/'),

    // Для поддоменов одного домена (zenythium.ru и api.zenythium.ru):
    // SESSION_DOMAIN=.zenythium.ru (с точкой в начале для всех поддоменов)
    // Для localhost: SESSION_DOMAIN=localhost или не задано
    'domain' => env('SESSION_DOMAIN'),

    // Для поддоменов одного домена с HTTPS:
    // SESSION_SECURE_COOKIE=true
    // SESSION_SAME_SITE=none (нужен для cross-origin запросов между поддоменами)
    // Для localhost (development):
    // SESSION_SECURE_COOKIE=false (или не задано)
    // SESSION_SAME_SITE=lax
    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    // ВАЖНО: Даже для поддоменов одного домена (zenythium.ru → api.zenythium.ru)
    // это cross-origin запрос, поэтому нужен 'none' (только с HTTPS)
    // Для localhost можно использовать 'lax'
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
