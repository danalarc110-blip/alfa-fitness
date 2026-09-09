<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cliente;
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
                if (!$abierta) throw ValidationException::withMessages(['cliente_id' => 'Este cliente no tiene una entrada abierta.']);
                $abierta->update(['fecha_salida' => now(), 'salida_registrada_por' => $empleadoId]);
                return;
            }
            if (!$cliente->activo) throw ValidationException::withMessages(['cliente_id' => 'No se puede registrar la entrada de una cuenta desactivada.']);
            if ($abierta) throw ValidationException::withMessages(['cliente_id' => 'Este cliente ya está dentro. Registra su salida antes de una nueva entrada.']);
            Asistencia::create(['cliente_id' => $clienteId, 'registrado_por' => $empleadoId, 'fecha_hora' => now(), 'tipo_acceso' => 'entrada']);
        });
    }
}
