<?php

namespace App\Support;

final class RangoAtleta
{
    public const RANGOS = [
        ['nombre' => 'Iniciado', 'minimo' => 0, 'imagen' => 'iniciado.svg', 'descripcion' => 'El primer paso también cuenta.'],
        ['nombre' => 'Intermedio', 'minimo' => 3, 'imagen' => 'intermedio.svg', 'descripcion' => 'Estás construyendo constancia.'],
        ['nombre' => 'Avanzado', 'minimo' => 8, 'imagen' => 'avanzado.svg', 'descripcion' => 'Cada registro suma a tu camino.'],
        ['nombre' => 'Élite', 'minimo' => 15, 'imagen' => 'elite.svg', 'descripcion' => 'Tu constancia deja huella.'],
    ];

    public static function paraRegistros(int $total): array
    {
        $indice = 0;
        foreach (self::RANGOS as $i => $rango) {
            if ($total >= $rango['minimo']) {
                $indice = $i;
            }
        }
        $rango = self::RANGOS[$indice];
        $siguiente = self::RANGOS[$indice + 1] ?? null;

        return $rango + [
            'total' => $total,
            'siguiente' => $siguiente,
            'faltan' => $siguiente ? max(0, $siguiente['minimo'] - $total) : 0,
            'porcentaje' => $siguiente ? min(100, (int) floor(($total - $rango['minimo']) / ($siguiente['minimo'] - $rango['minimo']) * 100)) : 100,
        ];
    }
}
