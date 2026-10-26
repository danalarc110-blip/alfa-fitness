<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Support\Acceso;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['exportaciones' => 10, 'operaciones-financieras' => 60] as $nombre => $limite) {
            RateLimiter::for($nombre, function (Request $request) use ($limite) {
                $user = $request->user();
                $clave = $user ? get_class($user).':'.$user->getAuthIdentifier() : $request->ip();

                return Limit::perMinute($limite)->by($clave);
            });
        }
        // Seed at login, before another device could change the password.
        Event::listen(Login::class, function ($event) {
            if (request()->hasSession() && $event->guard === 'cliente' && $event->user instanceof Cliente) {
                // Also seed OAuth logins, whose accounts may not have a local password.
                request()->session()->put('version_acceso_cliente', $event->user->version_acceso);
            }
            if (request()->hasSession() && $event->user->getAuthPassword()) {
                request()->session()->put('password_hash_'.$event->guard,
                    auth($event->guard)->hashPasswordForCookie($event->user->getAuthPassword()));
            }
        });
        foreach (['administrar', 'asistencia', 'membresias', 'operaciones', 'inventario', 'existencias', 'progreso', 'analitica_financiera', 'asignar_rutinas'] as $permiso) {
            Gate::define($permiso, fn ($user) => Acceso::permite($user, $permiso));
        }
    }
}
