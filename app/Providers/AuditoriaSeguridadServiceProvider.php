<?php

namespace App\Providers;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Ejercicio;
use App\Models\EjercicioCalificacion;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PausaMembresia;
use App\Models\PersonalRecord;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\Rutina;
use App\Models\RutinaDia;
use App\Models\RutinaEjercicio;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use App\Services\AuditoriaSeguridad;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditoriaSeguridadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Failed::class, function (Failed $evento): void {
            // Failed also carries the submitted password: never serialize the event/credentials.
            app(AuditoriaSeguridad::class)->loginFallido($evento->guard, $evento->user?->getAuthIdentifier());
        });

        foreach ([User::class, Cliente::class, Producto::class, PlanMembresia::class] as $modelo) {
            $modelo::updated(fn ($registro) => app(AuditoriaSeguridad::class)->modeloActualizado($registro));
        }
        foreach ([User::class, Cliente::class, Producto::class, PlanMembresia::class, Asistencia::class,
            Ejercicio::class, EjercicioCalificacion::class, Membresia::class, PagoMembresia::class,
            PausaMembresia::class, PersonalRecord::class, Rutina::class, RutinaDia::class,
            RutinaEjercicio::class, SolicitudMembresia::class, Venta::class, DetalleVenta::class] as $modelo) {
            $modelo::deleted(fn ($registro) => app(AuditoriaSeguridad::class)->modeloEliminado($registro));
        }
    }
}
