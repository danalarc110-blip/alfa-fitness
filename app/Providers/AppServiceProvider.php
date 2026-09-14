<?php

namespace App\Providers;

use App\Support\Acceso;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Seed at login, before another device could change the password.
        Event::listen(Login::class, function ($event) {
            if (request()->hasSession() && $event->user->getAuthPassword()) {
                request()->session()->put('password_hash_'.$event->guard,
                    auth($event->guard)->hashPasswordForCookie($event->user->getAuthPassword()));
            }
        });
        foreach (['administrar', 'asistencia', 'membresias', 'operaciones', 'inventario', 'progreso'] as $permiso) {
            Gate::define($permiso, fn ($user) => Acceso::permite($user, $permiso));
        }
    }
}
