<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EncabezadosSeguros
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        $devAssets = app()->environment('local')
            ? ' http://localhost:* http://127.0.0.1:*'
            : '';
        $devConnections = app()->environment('local')
            ? $devAssets.' ws://localhost:* ws://127.0.0.1:*'
            : '';
        $directivas = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'{$devAssets}",
            "script-src 'self' 'unsafe-inline'{$devAssets}",
            "connect-src 'self'{$devConnections}",
        ];
        if (app()->environment('production') && $request->isSecure()) {
            $directivas[] = 'upgrade-insecure-requests';
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $response->headers->set('Content-Security-Policy', implode('; ', $directivas));

        if (auth('web')->check() || auth('cliente')->check() || $request->is('establecer-contrasena*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
