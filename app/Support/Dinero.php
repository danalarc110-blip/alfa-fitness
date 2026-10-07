<?php

namespace App\Support;

use InvalidArgumentException;

class Dinero
{
    public static function centavos(string|int $importe): int
    {
        $importe = (string) $importe;
        if (! preg_match('/\A(-?)(\d+)(?:\.(\d{1,2}))?\z/', $importe, $partes)) {
            throw new InvalidArgumentException('El importe debe ser decimal con hasta dos posiciones.');
        }

        $centavos = (int) $partes[2] * 100 + (int) str_pad($partes[3] ?? '', 2, '0');

        return ($partes[1] ?? '') === '-' ? -$centavos : $centavos;
    }

    public static function decimal(int $centavos): string
    {
        return ($centavos < 0 ? '-' : '').intdiv(abs($centavos), 100).'.'.str_pad((string) (abs($centavos) % 100), 2, '0', STR_PAD_LEFT);
    }
}
