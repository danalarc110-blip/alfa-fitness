<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuentaActiva
{
    public function handle(Request $request, Closure $next)
    {
        // A client identity never retains a parallel employee session from legacy flows.
        if (Auth::guard('cliente')->check() && Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            if ($request->routeIs('dashboard')) {
                return redirect()->route('cliente.dashboard');
            }
        }
        foreach (['web', 'cliente'] as $guard) {
            $usuario = Auth::guard($guard)->user();
            if ($usuario && (! $usuario->activo || ($guard === 'web' && ! $usuario->password_establecida))) {
                Auth::guard('web')->logout();
                Auth::guard('cliente')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $mensaje = ! $usuario->activo ? 'Tu cuenta esta desactivada.' : 'Debes establecer tu contrasena desde el enlace de invitacion.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $mensaje], 403);
                }

                return redirect()->route('login')->withErrors(['cuenta' => $mensaje]);
            }
        }

        $cliente = Auth::guard('cliente')->user();
        if ($cliente?->legal_requerido && ! $cliente->legal_aceptado_en && ! $request->routeIs('cliente.legal.*', 'cliente.logout', 'cliente.salir', 'legal.*')) {
            return redirect()->route('cliente.legal.mostrar');
        }

        $admin = Auth::guard('web')->user();
        if ($admin?->rol === 'Administrador' && $admin->two_factor_confirmed_at) {
            $verificacion = $request->session()->get('two_factor_verified');
            if (! $verificacion || $verificacion['id'] !== $admin->id || $verificacion['confirmed_at'] !== $admin->two_factor_confirmed_at->timestamp || ! hash_equals($verificacion['password_hash'], hash('sha256', $admin->password)) || ! hash_equals($verificacion['factor_hash'] ?? '', hash('sha256', (string) $admin->two_factor_secret))) {
                Auth::guard('web')->logout();
                $request->session()->forget('two_factor_verified');
                $request->session()->regenerate();

                // Password must be entered first even for legacy sessions and remember cookies.
                return redirect()->route('login')->withErrors(['email' => 'Ingresa nuevamente para verificar el acceso en dos pasos.']);
            }
        }

        return $next($request);
    }
}
