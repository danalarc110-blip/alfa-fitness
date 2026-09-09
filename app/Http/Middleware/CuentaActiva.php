<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuentaActiva
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['web', 'cliente'] as $guard) {
            $usuario = Auth::guard($guard)->user();
            if ($usuario && (! $usuario->activo || ($guard === 'web' && ! $usuario->password_establecida))) {
                Auth::guard('web')->logout();
                Auth::guard('cliente')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $mensaje = ! $usuario->activo ? 'Tu cuenta esta desactivada.' : 'Debes establecer tu contrasena desde el enlace de invitacion.';
                if ($request->expectsJson()) return response()->json(['message' => $mensaje], 403);
                return redirect()->route('login')->withErrors(['cuenta' => $mensaje]);
            }
        }
        return $next($request);
    }
}
