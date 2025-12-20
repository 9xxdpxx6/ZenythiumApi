<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogCorsAndCookies
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Логируем только для /sanctum/csrf-cookie
        // Проверяем разными способами, так как путь может быть с ведущим слешем или без
        $path = $request->path();
        $fullUrl = $request->fullUrl();
        $isCsrfCookie = $path === 'sanctum/csrf-cookie' 
            || $request->is('sanctum/csrf-cookie')
            || $request->is('*/sanctum/csrf-cookie')
            || str_contains($fullUrl, 'sanctum/csrf-cookie');
        
        if ($isCsrfCookie) {
            $this->logRequest($request);
        }

        $response = $next($request);

        // Логируем ответ для /sanctum/csrf-cookie
        if ($isCsrfCookie) {
            // Проверяем, применен ли CORS middleware
            $corsApplied = $response->headers->has('Access-Control-Allow-Origin') 
                || $response->headers->has('Access-Control-Allow-Credentials');
            
            // Логируем все заголовки для отладки
            $allHeaders = $response->headers->all();
            Log::info('LogCorsAndCookies: Response headers check', [
                'path' => $path,
                'cors_applied' => $corsApplied,
                'all_headers_keys' => array_keys($allHeaders),
                'cors_headers' => [
                    'Access-Control-Allow-Origin' => $response->headers->get('Access-Control-Allow-Origin'),
                    'Access-Control-Allow-Credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
                    'Access-Control-Expose-Headers' => $response->headers->get('Access-Control-Expose-Headers'),
                ],
            ]);
            
            if (!$corsApplied) {
                Log::warning('CORS headers missing in response', [
                    'path' => $path,
                    'full_url' => $fullUrl,
                    'response_headers' => array_keys($allHeaders),
                    'all_headers' => $allHeaders,
                ]);
            }
            
            $this->logResponse($request, $response);
        }

        return $response;
    }

    /**
     * Логировать входящий запрос
     */
    private function logRequest(Request $request): void
    {
        $origin = $request->headers->get('origin');
        $referer = $request->headers->get('referer');
        $cookie = $request->headers->get('cookie');
        $userAgent = $request->headers->get('user-agent');
        
        // Проверяем, считается ли запрос stateful
        $sanctumConfig = config('sanctum');
        $statefulDomains = $sanctumConfig['stateful'] ?? [];
        $isStateful = false;
        $originHost = null;
        
        if ($origin) {
            $parsed = parse_url($origin);
            $originHost = $parsed['host'] ?? null;
            
            if ($originHost) {
                foreach ($statefulDomains as $domain) {
                    $domain = str_replace(['http://', 'https://'], '', $domain);
                    if ($originHost === $domain || str_ends_with($originHost, '.' . $domain)) {
                        $isStateful = true;
                        break;
                    }
                }
            }
        }

        Log::info('CSRF Cookie Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'origin' => $origin,
            'origin_host' => $originHost,
            'referer' => $referer,
            'has_cookie_header' => !empty($cookie),
            'cookie_header' => $cookie ? 'present' : 'missing',
            'user_agent' => $userAgent,
            'ip' => $request->ip(),
            'is_stateful' => $isStateful,
            'stateful_domains' => $statefulDomains,
        ]);
    }

    /**
     * Логировать исходящий ответ
     */
    private function logResponse(Request $request, Response $response): void
    {
        $origin = $request->headers->get('origin');
        
        // Получаем CORS заголовки из ответа
        $allowOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $allowCredentials = $response->headers->get('Access-Control-Allow-Credentials');
        $exposeHeaders = $response->headers->get('Access-Control-Expose-Headers');
        
        // Получаем Set-Cookie заголовки
        $setCookieHeaders = $response->headers->get('Set-Cookie', null, false);
        $hasSetCookie = !empty($setCookieHeaders);

        // Получаем конфигурацию
        $corsConfig = config('cors');
        $sessionConfig = config('session');
        $sanctumConfig = config('sanctum');

        Log::info('CSRF Cookie Response', [
            'status_code' => $response->getStatusCode(),
            'origin' => $origin,
            'cors_headers' => [
                'Access-Control-Allow-Origin' => $allowOrigin,
                'Access-Control-Allow-Credentials' => $allowCredentials,
                'Access-Control-Expose-Headers' => $exposeHeaders,
            ],
            'set_cookie' => [
                'present' => $hasSetCookie,
                'count' => $hasSetCookie ? count($setCookieHeaders) : 0,
                'headers' => $hasSetCookie ? array_map(function ($cookie) {
                    // Обрезаем значение cookie для безопасности, оставляем только атрибуты
                    return preg_replace('/=([^;]+)/', '=***', $cookie);
                }, $setCookieHeaders) : [],
            ],
            'config' => [
                'cors_supports_credentials' => $corsConfig['supports_credentials'] ?? false,
                'cors_allowed_origins' => $corsConfig['allowed_origins'] ?? [],
                'cors_exposed_headers' => $corsConfig['exposed_headers'] ?? [],
                'session_domain' => $sessionConfig['domain'] ?? null,
                'session_secure' => $sessionConfig['secure'] ?? null,
                'session_same_site' => $sessionConfig['same_site'] ?? null,
                'sanctum_stateful_domains' => $sanctumConfig['stateful'] ?? [],
            ],
            'origin_in_allowed' => $origin ? in_array($origin, $corsConfig['allowed_origins'] ?? []) : false,
        ]);
    }
}

