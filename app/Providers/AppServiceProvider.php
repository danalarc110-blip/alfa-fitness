<?php

namespace App\Providers;

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
        foreach (['administrar', 'asistencia', 'membresias', 'operaciones', 'inventario', 'progreso', 'clientes'] as $permiso) {
            \Illuminate\Support\Facades\Gate::define($permiso, fn ($user) => \App\Support\Acceso::permite($user, $permiso));
        }
    }
}
