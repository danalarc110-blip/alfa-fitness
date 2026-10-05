<?php

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\EncabezadosSeguros;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect insecure production requests before starting a session or validating CSRF.
        $middleware->prepend(EncabezadosSeguros::class);
        $middleware->web(append: [CuentaActiva::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password_actual', 'codigo']);
    })->create();
