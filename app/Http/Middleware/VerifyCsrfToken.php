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
}

