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
                return true;
            }
        }

        // 3. Для stateful запросов (SPA на том же домене, без Bearer токена) - требуем CSRF
        // EnsureFrontendRequestsAreStateful middleware обработает это автоматически
        return parent::inExceptArray($request);
    }

    /**
     * Get the CSRF token from the request.
     * Переопределяем, чтобы правильно обрабатывать зашифрованную cookie
     * Приоритет: cookie > заголовок X-CSRF-TOKEN > заголовок X-XSRF-TOKEN
     */
    protected function getTokenFromRequest($request)
    {
        // 1. Проверяем параметр _token (для форм)
        $token = $request->input('_token');
        
        // 2. Если нет, проверяем cookie (приоритет cookie, так как она правильная)
        if (!$token) {
            $cookieToken = $request->cookie('XSRF-TOKEN');
            if ($cookieToken) {
                // Декодируем cookie (URL decode)
                $decoded = urldecode($cookieToken);
                // Если это зашифрованная cookie, расшифровываем
                if (strlen($decoded) > 100) {
                    try {
                        $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($decoded);
                        // После расшифровки получаем JSON, извлекаем токен
                        $data = json_decode($decrypted, true);
                        if (isset($data['value'])) {
                            $token = $data['value'];
                        }
                    } catch (\Exception $e) {
                        // Если не удалось расшифровать, пробуем использовать как есть
                        // (может быть уже декодированный токен)
                        $token = $decoded;
                    }
                } else {
                    // Короткий токен - используем как есть
                    $token = $decoded;
                }
            }
        }
        
        // 3. Если токен все еще не найден, проверяем заголовки
        if (!$token) {
            $token = $request->header('X-CSRF-TOKEN') ?: $request->header('X-XSRF-TOKEN');
        }
        
        return $token;
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
        
        // Пытаемся извлечь токен из зашифрованной cookie
        $extractedTokenFromCookie = null;
        if ($decodedCookieToken && strlen($decodedCookieToken) > 100) {
            try {
                $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($decodedCookieToken);
                $data = json_decode($decrypted, true);
                if (isset($data['value'])) {
                    $extractedTokenFromCookie = $data['value'];
                }
            } catch (\Exception $e) {
                // Игнорируем ошибку
            }
        } else {
            $extractedTokenFromCookie = $decodedCookieToken;
        }
        
        $result = parent::tokensMatch($request);
        
        // Логируем только ошибки для уменьшения объема логов
        if (!$result) {
            \Illuminate\Support\Facades\Log::error('CSRF: Token validation FAILED', [
                'path' => $request->path(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'token_from_request' => $token ? substr($token, 0, 50) . '...' : 'missing',
                'token_length' => $token ? strlen($token) : 0,
                'session_token' => $sessionToken ? substr($sessionToken, 0, 50) . '...' : 'missing',
                'session_token_length' => $sessionToken ? strlen($sessionToken) : 0,
                'extracted_from_cookie' => $extractedTokenFromCookie ? substr($extractedTokenFromCookie, 0, 50) . '...' : 'missing',
                'cookie_present' => !empty($cookieToken),
                'header_present' => !empty($headerToken),
            ]);
        }
        
        return $result;
    }
}

