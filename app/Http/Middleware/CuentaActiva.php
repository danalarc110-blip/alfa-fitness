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
            // Unstamped sessions predate this mechanism and belong to version zero.
            $sesionRevocada = $usuario && $guard === 'cliente'
                && (int) $request->session()->get('access_version_cliente', 0) !== $usuario->access_version;
            if ($usuario && (! $usuario->activo || ($guard === 'web' && ! $usuario->password_establecida) || $sesionRevocada)) {
                foreach (['web', 'cliente'] as $cerrarGuard) {
                    if ($sesionRevocada) {
                        // A rejected old session must not revoke newer remembered sessions.
                        Auth::guard($cerrarGuard)->logoutCurrentDevice();
                    } else {
                        Auth::guard($cerrarGuard)->logout();
                    }
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $mensaje = match (true) {
                    ! $usuario->activo => 'Tu cuenta esta desactivada.',
                    $sesionRevocada => 'Tu sesión ha expirado. Inicia sesión nuevamente.',
                    default => 'Debes establecer tu contrasena desde el enlace de invitacion.',
                };
                if ($request->expectsJson()) {
                    return response()->json(['message' => $mensaje], $usuario->activo && $sesionRevocada ? 401 : 403);
                }

                return redirect()->route('login')->withErrors(['cuenta' => $mensaje]);
            }
        }

        return $next($request);
    }
}
