<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cliente;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistroAsistencia
{
    public const TARIFA_ENTRADA_DIARIA = '2.00';

    public function evaluarAcceso(Cliente $cliente): array
    {
        $acceso = ['estado' => 'inactivo', 'requiere_pago' => false, 'importe' => '0.00',
            'puede_entrar' => false, 'motivo' => 'No se puede registrar la entrada de una cuenta desactivada.',
            'membresia_estado' => 'Cuenta inactiva', 'membresia_fin' => null];
        if (! $cliente->activo) {
            return $acceso;
        }

        $cliente->loadMissing(['membresiasParaAcceso.pausas', 'ultimaMembresiaRegistrada']);
        $vigente = $cliente->membresiasParaAcceso->first(fn ($membresia) => $membresia->inicio->lte(today()));
        $pausada = $cliente->membresiasParaAcceso->first(fn ($membresia) => $membresia->pausaVigente() !== null);
        $referencia = $pausada ?? $vigente ?? $cliente->membresiasParaAcceso->sortBy('inicio')->first() ?? $cliente->ultimaMembresiaRegistrada;
        $acceso['membresia_fin'] = $referencia?->fin->toDateString();
        $acceso['membresia_estado'] = match (true) {
            $referencia === null => 'Sin membresía',
            $referencia->cancelada => 'Cancelada',
            $pausada !== null => 'En Pausa',
            $referencia->inicio->isAfter(today()) => 'Programada',
            $referencia->fin->isBefore(today()) => 'Vencida',
            default => 'Vigente',
        };
        if ($pausada) {
            $acceso['estado'] = 'pausa';
            $acceso['motivo'] = 'La membresía del socio está congelada/en pausa temporal. Debe reanudar su membresía para ingresar.';

            return $acceso;
        }

        return array_replace($acceso, [
            'estado' => $vigente ? 'membresia' : 'pago_diario',
            'requiere_pago' => $vigente === null,
            'importe' => $vigente ? '0.00' : self::TARIFA_ENTRADA_DIARIA,
            'puede_entrar' => true,
            'motivo' => $vigente ? 'Entrada incluida en la membresía vigente.' : 'Sin membresía vigente. Cada entrada requiere un cobro de $2.00.',
        ]);
    }

    public function registrar(int $clienteId, ?int $empleadoId, bool $salida, bool $pagoConfirmado = false, ?string $entradaUuid = null): Asistencia
    {
        if (! $salida && $entradaUuid !== null) {
            if (! Str::isUuid($entradaUuid)) {
                throw ValidationException::withMessages(['entrada_uuid' => 'El identificador de la entrada no es válido.']);
            }
            $entradaUuid = strtolower($entradaUuid);
        }
        try {
            $asistencia = DB::transaction(function () use ($clienteId, $empleadoId, $salida, $pagoConfirmado, $entradaUuid) {
                $cliente = Cliente::whereKey($clienteId)->lockForUpdate()->firstOrFail();
                if (! $salida && $entradaUuid) {
                    $existente = Asistencia::where('entrada_uuid', $entradaUuid)->first();
                    if ($existente) {
                        $this->autorizarReintento($existente, $clienteId, $empleadoId);

                        return $existente->load('pagoEntrada');
                    }
                }
                $abierta = Asistencia::where('cliente_id', $clienteId)->whereNull('fecha_salida')->latest('fecha_hora')->first();
                if ($salida) {
                    if (! $abierta) {
                        throw ValidationException::withMessages(['cliente_id' => 'Este cliente no tiene una entrada abierta.']);
                    }
                    $abierta->update(['fecha_salida' => now(), 'salida_registrada_por' => $empleadoId]);

                    return $abierta->load('pagoEntrada');
                }
                $acceso = $this->evaluarAcceso($cliente);
                if (! $acceso['puede_entrar']) {
                    throw ValidationException::withMessages(['cliente_id' => $acceso['motivo']]);
                }
                if ($abierta && ! $abierta->es_huerfana) {
                    throw ValidationException::withMessages(['cliente_id' => 'Este cliente ya está dentro. Registra su salida antes de una nueva entrada.']);
                }
                if ($acceso['requiere_pago']) {
                    if (! $pagoConfirmado || $empleadoId === null) {
                        throw ValidationException::withMessages(['pago_entrada_confirmado' => 'Confirma que recibiste los $2.00 de esta entrada antes de registrarla.']);
                    }
                    if (! $entradaUuid) {
                        throw ValidationException::withMessages(['entrada_uuid' => 'La entrada con cobro requiere un identificador para evitar duplicados.']);
                    }
                }

                // Si hay una visita abierta pero es huérfana de más de 12 horas, auto-cerrarla para permitir nuevo ingreso
                if ($abierta && $abierta->fecha_hora->lt(now()->subHours(12))) {
                    $abierta->update([
                        'fecha_salida' => $abierta->fecha_hora->copy()->addHours(2),
                        'salida_registrada_por' => $empleadoId,
                    ]);
                    $abierta = null;
                }

                $nueva = Asistencia::create(['cliente_id' => $clienteId, 'registrado_por' => $empleadoId, 'fecha_hora' => now(),
                    'tipo_acceso' => 'entrada', 'entrada_uuid' => $entradaUuid]);
                if ($acceso['requiere_pago']) {
                    $nueva->pagoEntrada()->create(['registrado_por' => $empleadoId, 'importe' => self::TARIFA_ENTRADA_DIARIA, 'pagado_en' => now()]);
                }

                return $nueva->load('pagoEntrada');
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $asistencia = $entradaUuid && ! $salida ? Asistencia::where('entrada_uuid', $entradaUuid)->first() : null;
            if (! $asistencia) {
                throw $exception;
            }
            $this->autorizarReintento($asistencia, $clienteId, $empleadoId);
            $asistencia->load('pagoEntrada');
        }
        Cache::forget('aforo_en_vivo');

        return $asistencia;
    }

    private function autorizarReintento(Asistencia $asistencia, int $clienteId, ?int $empleadoId): void
    {
        abort_unless($asistencia->cliente_id === $clienteId && $asistencia->registrado_por === $empleadoId, 403);
    }
}
