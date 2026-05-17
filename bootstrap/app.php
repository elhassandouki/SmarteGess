<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\EnsureDocumentLimit;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeadersMiddleware::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'tenant.active' => EnsureTenantIsActive::class,
            'plan.documents' => EnsureDocumentLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            $request = request();
            Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'user_id' => auth()->id(),
                'trace_id' => $request?->header('X-Request-Id'),
            ]);
        });
    })->create();
