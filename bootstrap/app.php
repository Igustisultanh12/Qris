<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'assign.request.id' => \App\Http\Middleware\AssignRequestId::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuthenticate::class,
            'api.rate' => \App\Http\Middleware\ApiRateLimiter::class,
            'api.logger' => \App\Http\Middleware\ApiLoggerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Http\Responses\ApiResponse::error('Unauthenticated', null, 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Http\Responses\ApiResponse::error('Endpoint or resource not found', null, 404);
            }
        });
    })->create();
