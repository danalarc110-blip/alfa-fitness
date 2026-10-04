<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/** Audit only fixed event names, internal IDs and explicitly safe attributes. */
class AuditoriaSeguridad
{
    private const TABLAS = ['users', 'clientes', 'productos', 'planes_membresia', 'asistencias',
        'ejercicios', 'ejercicio_calificaciones', 'membresias', 'pagos_membresia', 'pausas_membresia',
        'personal_records', 'rutinas', 'rutina_dias', 'rutina_ejercicios', 'solicitudes_membresia', 'ventas', 'detalle_ventas'];

    public function loginFallido(string $guard, ?int $cuentaId): void
    {
        $this->registrar('login_fallido', [
            'guard' => in_array($guard, ['web', 'cliente'], true) ? $guard : 'desconocido',
            'cuenta_id' => $cuentaId,
        ]);
    }

    public function dosFactoresFallido(int $cuentaId): void
    {
        $this->registrar('dos_factores_fallido', ['guard' => 'web', 'cuenta_id' => $cuentaId]);
    }

    public function modeloActualizado(Model $modelo): void
    {
        $cambios = [];
        if (($modelo instanceof Producto || $modelo instanceof PlanMembresia) && $modelo->wasChanged('precio')) {
            $cambios[] = ['precio_cambiado', ['anterior' => (float) $modelo->getOriginal('precio'), 'nuevo' => (float) $modelo->precio]];
        }
        if ($modelo instanceof User && $modelo->wasChanged('rol')) {
            $anterior = $modelo->getOriginal('rol');
            $nuevo = $modelo->rol;
            $cambios[] = ['rol_cambiado', [
                'anterior' => in_array($anterior, Acceso::ROLES, true) ? $anterior : 'desconocido',
                'nuevo' => in_array($nuevo, Acceso::ROLES, true) ? $nuevo : 'desconocido',
            ]];
        }
        if (($modelo instanceof User || $modelo instanceof Cliente) && $modelo->wasChanged('activo')) {
            $cambios[] = ['acceso_cambiado', ['anterior' => (bool) $modelo->getOriginal('activo'), 'nuevo' => (bool) $modelo->activo]];
        }
        if ($modelo instanceof User && $modelo->wasChanged('two_factor_confirmed_at')) {
            $cambios[] = ['dos_factores_cambiado', [
                'anterior' => $modelo->getOriginal('two_factor_confirmed_at') !== null,
                'nuevo' => $modelo->two_factor_confirmed_at !== null,
            ]];
        }
        foreach ($cambios as [$evento, $datos]) {
            $this->registrarModelo($modelo, $evento, $datos);
        }
    }

    public function modeloEliminado(Model $modelo): void
    {
        $this->registrarModelo($modelo, 'registro_eliminado');
    }

    private function registrarModelo(Model $modelo, string $evento, array $datos = []): void
    {
        if (! in_array($modelo->getTable(), self::TABLAS, true)) {
            return;
        }
        $guard = auth('cliente')->check() ? 'cliente' : (auth('web')->check() ? 'web' : null);
        $contexto = $datos + [
            'tabla' => $modelo->getTable(),
            'registro_id' => (int) $modelo->getKey(),
            'guard' => $guard,
            'actor_id' => $guard ? auth($guard)->id() : null,
        ];
        // Do not report an action as completed when its surrounding transaction rolled back.
        $modelo->getConnection()->afterCommit(fn () => $this->registrar($evento, $contexto));
    }

    private function registrar(string $evento, array $contexto): void
    {
        try {
            Log::channel('auditoria')->info($evento, $contexto);
        } catch (\Throwable $error) {
            // A full disk must not turn a completed payment into a misleading HTTP failure.
            // This fixed fallback deliberately excludes exception messages and event payloads.
            error_log('Alpha Fitness: auditoria no disponible; evento='.$evento.'; excepcion='.get_class($error));
        }
    }
}
