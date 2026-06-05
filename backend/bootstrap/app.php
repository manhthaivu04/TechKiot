<?php

use App\Http\Middleware\EnsureRole;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        RepositoryServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register the role-check alias
        $middleware->alias([
            'role' => EnsureRole::class,
            'feature' => \App\Http\Middleware\EnsureFeature::class,
        ]);

        // Ensure API responses are always JSON
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unified JSON error responses
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code'    => \App\Enums\ApiCode::NOT_FOUND->value,
                    'message' => 'Tài nguyên không tồn tại.',
                ], 404);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code'    => \App\Enums\ApiCode::VALIDATION_ERROR->value,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code'    => \App\Enums\ApiCode::DOMAIN_ERROR->value,
                    'message' => $e->getMessage(),
                ], 400);
            }
        });
    })->create();
