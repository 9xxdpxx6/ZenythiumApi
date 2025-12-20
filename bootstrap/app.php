<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\LogCorsAndCookies::class,
        ]);
        
        // ВАЖНО: Применяем CORS middleware к web routes, чтобы он работал для /sanctum/csrf-cookie
        // В Laravel 11 CORS применяется автоматически к API routes, но не к web routes
        // Также применяем EnsureFrontendRequestsAreStateful к web routes для /sanctum/csrf-cookie
        $middleware->web(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        // Принудительно добавляем CORS заголовки ПОСЛЕ всех других middleware
        // Это гарантирует, что заголовки не будут удалены другими middleware
        $middleware->web(append: [
            \App\Http\Middleware\ForceCorsHeaders::class,
            \App\Http\Middleware\LogCorsAndCookies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
