<?php

namespace App\Services;

use App\Support\Dinero;
use Illuminate\Validation\ValidationException;

final class PagoSimulado
{
    public function resolver(array $datos, int $totalCentavos): array
    {
        if (empty($datos['pago_simulado'])) {
            return ['pago_simulado' => false];
        }

        $pago = ['pago_simulado' => true, 'referencia_pago' => 'SIM-'.strtoupper($datos['venta_uuid'])];
        if ($datos['metodo_pago'] === 'Efectivo') {
            if (! isset($datos['efectivo_recibido'])) {
                throw ValidationException::withMessages(['efectivo_recibido' => 'Indica cuánto efectivo de prueba recibes.']);
            }
            $recibido = Dinero::centavos((string) $datos['efectivo_recibido']);
            if ($recibido < $totalCentavos) {
                throw ValidationException::withMessages(['efectivo_recibido' => 'El efectivo recibido es menor que el total de la venta.']);
            }
            $pago['efectivo_recibido'] = Dinero::decimal($recibido);
            $pago['cambio'] = Dinero::decimal($recibido - $totalCentavos);
        } elseif ($datos['metodo_pago'] === 'Tarjeta' && ($datos['resultado_tarjeta'] ?? null) !== 'aprobada') {
            throw ValidationException::withMessages(['resultado_tarjeta' => 'Tarjeta de prueba rechazada. No se registró la venta ni se descontaron existencias. Elige otro resultado o método para reintentar.']);
        }

        return $pago;
    }
}
