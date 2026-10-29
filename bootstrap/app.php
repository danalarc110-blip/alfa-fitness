<?php

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\EncabezadosSeguros;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (filter_var(env('RENDER', false), FILTER_VALIDATE_BOOL)) {
            // Render terminates TLS before forwarding requests to Apache.
            // Keep the original Host authoritative; trust only transport and client IP headers.
            $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
        $middleware->web(append: [EncabezadosSeguros::class, CuentaActiva::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password_actual']);
    })->create();
