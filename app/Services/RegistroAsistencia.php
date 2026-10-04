<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\PausaMembresia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistroAsistencia
{
    public function registrar(int $clienteId, ?int $empleadoId, bool $salida): void
    {
        DB::transaction(function () use ($clienteId, $empleadoId, $salida) {
            $cliente = Cliente::whereKey($clienteId)->lockForUpdate()->firstOrFail();
            $abierta = Asistencia::where('cliente_id', $clienteId)->whereNull('fecha_salida')->latest('fecha_hora')->first();
            if ($salida) {
                if (! $abierta) {
                    throw ValidationException::withMessages(['cliente_id' => 'Este cliente no tiene una entrada abierta.']);
                }
                $abierta->update(['fecha_salida' => now(), 'salida_registrada_por' => $empleadoId]);
                Cache::forget('aforo_en_vivo');

                return;
            }
            if (! $cliente->activo) {
                throw ValidationException::withMessages(['cliente_id' => 'No se puede registrar la entrada de una cuenta desactivada.']);
            }

            $pausaActiva = PausaMembresia::where('cliente_id', $clienteId)
                ->where('estado', 'aprobada')
                ->where('inicio_pausa', '<=', today())
                ->where('fin_pausa_estimada', '>=', today())
                ->whereHas('membresia', fn ($q) => $q->where('cancelada', false)->where('fin', '>=', today()))
                ->exists();
            if ($pausaActiva) {
                throw ValidationException::withMessages(['cliente_id' => 'La membresía del socio está congelada/en pausa temporal. Debe reanudar su membresía para ingresar.']);
            }

            // Si hay una visita abierta pero es huérfana de más de 12 horas, auto-cerrarla para permitir nuevo ingreso
            if ($abierta && $abierta->fecha_hora->lt(now()->subHours(12))) {
                $abierta->update([
                    'fecha_salida' => $abierta->fecha_hora->copy()->addHours(2),
                    'salida_registrada_por' => $empleadoId,
                ]);
                $abierta = null;
            }

            if ($abierta) {
                throw ValidationException::withMessages(['cliente_id' => 'Este cliente ya está dentro. Registra su salida antes de una nueva entrada.']);
            }
            Asistencia::create(['cliente_id' => $clienteId, 'registrado_por' => $empleadoId, 'fecha_hora' => now(), 'tipo_acceso' => 'entrada']);
            Cache::forget('aforo_en_vivo');
        });
    }
}
