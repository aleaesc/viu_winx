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
        // REMOVED: EnsureFrontendRequestsAreStateful was causing 500 errors
        // It's applied per-route where needed instead
        
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        $middleware->appendToGroup('web', [\App\Http\Middleware\SecurityHeaders::class]);
        // REMOVED: SecurityHeaders from API - might interfere with JSON responses
        // $middleware->appendToGroup('api', [\App\Http\Middleware\SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                \Illuminate\Support\Facades\Log::error('API Exception caught', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Server error',
                    'details' => config('app.debug') ? $e->getMessage() : 'Please try again later',
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null
                ], 500);
            }
        });
    })->create();
