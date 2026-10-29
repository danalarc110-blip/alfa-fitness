<?php

namespace App\Http\Middleware;

use App\Support\AvisoPrivacidad;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExigirAvisoPrivacidad
{
    public function handle(Request $request, Closure $next): Response
    {
        // Linking an existing, authenticated account is checked again in the callback.
        $vinculando = $request->routeIs('cliente.google.callback')
            && Auth::guard('cliente')->check() && $request->session()->has('google_link');

        if (! AvisoPrivacidad::vigente($request) && ! $vinculando) {
            return redirect()->route('login')->withErrors([
                'privacy_notice' => 'Lee la política de privacidad y confirma su lectura antes de continuar.',
            ]);
        }

        return $next($request);
    }
}
