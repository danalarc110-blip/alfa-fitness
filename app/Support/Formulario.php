<?php

namespace App\Support;

final class Formulario
{
    /** Recupera texto anterior sin intentar imprimir estructuras de una petición inválida. */
    public static function anterior(string $campo, mixed $predeterminado = ''): string
    {
        $valor = old($campo, $predeterminado);
        if (! is_string($valor) && ! is_int($valor) && ! is_float($valor)) {
            $valor = $predeterminado;
        }

        return is_string($valor) || is_int($valor) || is_float($valor) ? (string) $valor : '';
    }
}
