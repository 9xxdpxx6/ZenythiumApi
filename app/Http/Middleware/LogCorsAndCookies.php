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
        
        $response = $next($request);

        // Логируем ответ для /sanctum/csrf-cookie
        if ($isCsrfCookie) {
            // Проверяем, применен ли CORS middleware
            $corsApplied = $response->headers->has('Access-Control-Allow-Origin') 
                || $response->headers->has('Access-Control-Allow-Credentials');
            
            // Логируем только если CORS заголовки отсутствуют
            if (!$corsApplied) {
                Log::warning('CORS headers missing in response', [
                    'path' => $path,
                    'full_url' => $fullUrl,
                    'response_headers' => array_keys($response->headers->all()),
                ]);
            }
        }

        return $response;
    }

}

