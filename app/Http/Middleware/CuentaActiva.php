<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuentaActiva
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['web', 'cliente'] as $guard) {
            $usuario = Auth::guard($guard)->user();
            $sesionRevocada = $usuario instanceof Cliente && $guard === 'cliente'
                && $request->session()->get('version_acceso_cliente', 0) !== $usuario->version_acceso;
            if ($usuario && (! $usuario->activo || ($guard === 'web' && ! $usuario->password_establecida) || $sesionRevocada)) {
                if ($sesionRevocada) {
                    // Reject this device without rotating the token used by new, legitimate logins.
                    Auth::guard('web')->logoutCurrentDevice();
                    Auth::guard('cliente')->logoutCurrentDevice();
                } else {
                    Auth::guard('web')->logout();
                    Auth::guard('cliente')->logout();
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $mensaje = match (true) {
                    ! $usuario->activo => 'Tu cuenta esta desactivada.',
                    $sesionRevocada => 'Tu sesión fue revocada. Inicia sesión nuevamente.',
                    default => 'Debes establecer tu contrasena desde el enlace de invitacion.',
                };
                if ($request->expectsJson()) {
                    return response()->json(['message' => $mensaje], 403);
                }

                return redirect()->route('login')->withErrors(['cuenta' => $mensaje]);
            }
            if ($usuario instanceof Cliente && $guard === 'cliente') {
                // Existing sessions at version zero remain valid on the first deployment.
                $request->session()->put('version_acceso_cliente', $usuario->version_acceso);
            }
        }

        return $next($request);
    }
}
