<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register route middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'check.user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
            'password.rehash' => \App\Http\Middleware\PasswordRehashMiddleware::class,
            'audit' => \App\Http\Middleware\AuditMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'sanitize.input' => \App\Http\Middleware\SanitizeInputMiddleware::class,
        ]);

        // Register global middleware
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // SECURITY: Don't report authentication errors to avoid log spam
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
        ]);
    })->create();