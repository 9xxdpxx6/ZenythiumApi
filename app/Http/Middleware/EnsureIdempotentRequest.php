<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Идемпотентность мутирующих запросов по заголовку Idempotency-Key.
 *
 * На плохой сети клиент иногда получает сетевую ошибку/таймаут уже после того,
 * как сервер успел обработать запрос, и автоматически повторяет тот же
 * POST/PUT/PATCH/DELETE. Без этой защиты повтор создаёт дубль (вторую
 * тренировку, второй цикл и т.д.). Если клиент присылает Idempotency-Key,
 * повторный запрос с тем же ключом получает тот же ответ, что и первый, без
 * повторного выполнения контроллера.
 *
 * Заголовок опционален: запросы без него ведут себя как раньше.
 */
final class EnsureIdempotentRequest
{
    private const CACHE_TTL_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request, $key);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response($cached['content'], $cached['status'])
                ->header('Content-Type', $cached['contentType']);
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'content' => $response->getContent(),
                'contentType' => $response->headers->get('Content-Type') ?? 'application/json',
            ], self::CACHE_TTL_SECONDS);
        }

        return $response;
    }

    private function buildCacheKey(Request $request, string $key): string
    {
        $userId = $request->user()?->id ?? 'guest';

        return sprintf('idempotency:%s:%s:%s:%s', $userId, $request->method(), $request->path(), $key);
    }
}
