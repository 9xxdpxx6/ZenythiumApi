<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

final class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Исключения обрабатываются динамически в методах ниже
    ];

    /**
     * Determine if the request should be excluded from CSRF verification.
     * 
     * Безопасность:
     * 1. Bearer токены - исключаем (CSRF не работает с Bearer токенами)
     *    CSRF атаки работают только с cookies, а Bearer токены в заголовке Authorization
     *    не отправляются браузером автоматически, поэтому CSRF атаки невозможны.
     * 
     * 2. API маршруты с Origin заголовком (кросс-доменные) - исключаем
     *    Если запрос идет с другого домена (есть Origin), это stateless запрос,
     *    CSRF не нужен, так как cookies не отправляются автоматически.
     * 
     * 3. Stateful запросы (SPA на том же домене, без Bearer токена) - требуем CSRF
     *    EnsureFrontendRequestsAreStateful middleware определяет stateful запросы
     *    и применяет CSRF защиту автоматически.
     */
    protected function inExceptArray($request): bool
    {
        // 1. Если запрос использует Bearer токен - исключаем из CSRF
        // Это безопасно: CSRF атаки работают только с cookies (stateful),
        // Bearer токены в заголовке не отправляются браузером автоматически
        if ($request->bearerToken()) {
            \Illuminate\Support\Facades\Log::info('CSRF: Excluded - Bearer token present');
            return true;
        }

        // 2. Если запрос идет с другого домена (есть Origin) - это stateless запрос
        // CSRF не нужен, так как cookies не отправляются автоматически
        $origin = $request->headers->get('origin');
        if ($origin) {
            $statefulDomains = config('sanctum.stateful', []);
            $originHost = parse_url($origin, PHP_URL_HOST);
            
            // Проверяем, не является ли Origin одним из stateful доменов
            $isStateful = false;
            foreach ($statefulDomains as $domain) {
                $domain = str_replace(['http://', 'https://'], '', $domain);
                if ($originHost === $domain || str_ends_with($originHost, '.' . $domain)) {
                    $isStateful = true;
                    break;
                }
            }
            
            // Если Origin не в списке stateful доменов - это stateless запрос, исключаем CSRF
            if (!$isStateful) {
                \Illuminate\Support\Facades\Log::info('CSRF: Excluded - Stateless request', [
                    'origin' => $origin,
                    'origin_host' => $originHost,
                    'stateful_domains' => $statefulDomains,
                ]);
                return true;
            }
            
            // Для stateful запросов логируем детали
            \Illuminate\Support\Facades\Log::info('CSRF: Stateful request - CSRF required', [
                'origin' => $origin,
                'origin_host' => $originHost,
                'path' => $request->path(),
                'method' => $request->method(),
                'has_csrf_token' => $request->hasHeader('X-XSRF-TOKEN'),
                'csrf_token' => $request->header('X-XSRF-TOKEN') ? 'present' : 'missing',
                'has_cookie' => $request->hasCookie('XSRF-TOKEN'),
                'cookie_value' => $request->cookie('XSRF-TOKEN') ? 'present' : 'missing',
            ]);
        }

        // 3. Для stateful запросов (SPA на том же домене, без Bearer токена) - требуем CSRF
        // EnsureFrontendRequestsAreStateful middleware обработает это автоматически
        return parent::inExceptArray($request);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     * Переопределяем для детального логирования
     */
    protected function tokensMatch($request): bool
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $request->session()->token();
        
        // Получаем токены из разных источников для сравнения
        $cookieToken = $request->cookie('XSRF-TOKEN');
        $headerToken = $request->header('X-XSRF-TOKEN');
        $csrfHeaderToken = $request->header('X-CSRF-TOKEN');
        
        // Декодируем cookie токен для сравнения
        $decodedCookieToken = $cookieToken ? urldecode($cookieToken) : null;
        
        // Детальное логирование для отладки
        \Illuminate\Support\Facades\Log::info('CSRF: Token validation', [
            'path' => $request->path(),
            'method' => $request->method(),
            'session_id' => $request->session()->getId(),
            'has_token_in_request' => !empty($token),
            'token_from_getTokenFromRequest' => $token ? substr($token, 0, 50) . '...' : 'missing',
            'token_length' => $token ? strlen($token) : 0,
            'has_session_token' => !empty($sessionToken),
            'session_token' => $sessionToken ? substr($sessionToken, 0, 50) . '...' : 'missing',
            'session_token_length' => $sessionToken ? strlen($sessionToken) : 0,
            'tokens_match' => hash_equals($sessionToken, $token),
            'cookie_token_raw' => $cookieToken ? substr($cookieToken, 0, 50) . '...' : 'missing',
            'cookie_token_decoded' => $decodedCookieToken ? substr($decodedCookieToken, 0, 50) . '...' : 'missing',
            'header_x_xsrf_token' => $headerToken ? substr($headerToken, 0, 50) . '...' : 'missing',
            'header_x_csrf_token' => $csrfHeaderToken ? substr($csrfHeaderToken, 0, 50) . '...' : 'missing',
            'cookie_matches_session' => $decodedCookieToken ? hash_equals($sessionToken, $decodedCookieToken) : false,
            'header_matches_session' => $headerToken ? hash_equals($sessionToken, $headerToken) : false,
            'all_cookies' => array_keys($request->cookies->all()),
        ]);
        
        $result = parent::tokensMatch($request);
        
        if (!$result) {
            \Illuminate\Support\Facades\Log::error('CSRF: Token validation FAILED', [
                'path' => $request->path(),
                'session_id' => $request->session()->getId(),
                'token_from_request' => $token,
                'session_token' => $sessionToken,
            ]);
        }
        
        return $result;
    }
}

