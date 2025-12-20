<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceCorsHeaders
{
    /**
     * Handle an incoming request.
     * Принудительно добавляет CORS заголовки для /sanctum/csrf-cookie
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Применяем только для /sanctum/csrf-cookie
        if ($request->is('sanctum/csrf-cookie') || str_contains($request->path(), 'sanctum/csrf-cookie')) {
            $origin = $request->headers->get('origin');
            $corsConfig = config('cors');
            
            // Проверяем, разрешен ли Origin
            $allowedOrigins = $corsConfig['allowed_origins'] ?? [];
            $isOriginAllowed = $origin && in_array($origin, $allowedOrigins);
            
            if ($isOriginAllowed) {
                // Принудительно устанавливаем CORS заголовки
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                
                // Exposed headers для cookies
                $exposedHeaders = $corsConfig['exposed_headers'] ?? [];
                if (!empty($exposedHeaders)) {
                    $response->headers->set('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
                }
                
                // Allow-Methods и Allow-Headers для preflight
                $response->headers->set('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowed_methods'] ?? ['*']));
                $response->headers->set('Access-Control-Allow-Headers', '*');
            }
        }

        return $response;
    }
}

