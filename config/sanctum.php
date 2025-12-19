<?php

use Laravel\Sanctum\Sanctum;

// Получаем домен из FRONTEND_URL если он задан
$frontendUrl = env('FRONTEND_URL');
$frontendDomain = null;
if ($frontendUrl) {
    $parsed = parse_url($frontendUrl);
    if ($parsed && isset($parsed['host'])) {
        $frontendDomain = $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}

// Базовые домены для разработки
$defaultDomains = 'localhost,localhost:3000,localhost:8080,localhost:8100,127.0.0.1,127.0.0.1:8000,10.0.2.2,capacitor://localhost,::1';

// Добавляем фронтенд домен если он задан
if ($frontendDomain) {
    $defaultDomains .= ',' . $frontendDomain;
}

// Добавляем текущий URL приложения
$defaultDomains .= ',' . str_replace(['http://', 'https://'], '', Sanctum::currentApplicationUrlWithPort());

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', $defaultDomains)),

    'guard' => ['web'],

    'expiration' => 60 * 24 * 30, // 30 days

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],

];
