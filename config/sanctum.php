<?php

use Laravel\Sanctum\Sanctum;

$frontendUrl = env('FRONTEND_URL');
$frontendDomain = null;

if ($frontendUrl) {
    $parsed = parse_url($frontendUrl);
    if ($parsed && isset($parsed['host'])) {
        $frontendDomain = $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}

$defaultDomains = 'localhost,localhost:3000,localhost:8080,localhost:8100,127.0.0.1,127.0.0.1:8000,10.0.2.2,capacitor://localhost,::1';

if ($frontendDomain) {
    $defaultDomains .= ',' . $frontendDomain;
}

$defaultDomains .= ',' . str_replace(['http://', 'https://'], '', Sanctum::currentApplicationUrlWithPort());

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', $defaultDomains)),

    'cookie' => [
        'name' => 'XSRF-TOKEN',
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', '.zenythium.ru'), // важна точка!
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => false, // чтобы фронт видел токен
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
    ],

    'guard' => ['web'],

    'expiration' => 60 * 24 * 30, // 30 дней

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
