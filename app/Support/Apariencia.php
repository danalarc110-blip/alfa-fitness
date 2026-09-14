<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Apariencia
{
    public const PALETAS = [
        'light' => ['primary' => '#8a6200', 'accent' => '#176b63', 'background' => '#f3f4f6', 'surface' => '#ffffff', 'text' => '#18212f'],
        'dark' => ['primary' => '#facc15', 'accent' => '#5ed4bf', 'background' => '#101216', 'surface' => '#1a1e25', 'text' => '#f1f4f9'],
    ];

    public static function contraste(string $a, string $b): float
    {
        $luminancia = static function (string $hex): float {
            $rgb = array_map(static function ($canal) {
                $valor = hexdec($canal) / 255;

                return $valor <= 0.04045 ? $valor / 12.92 : (($valor + 0.055) / 1.055) ** 2.4;
            }, str_split(substr($hex, 1), 2));

            return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
        };
        $a = $luminancia($a);
        $b = $luminancia($b);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public static function legible(array $colores): bool
    {
        foreach (array_keys(self::PALETAS['light']) as $key) {
            if (! is_string($colores[$key] ?? null) || ! preg_match('/^#[0-9a-fA-F]{6}$/D', $colores[$key])) {
                return false;
            }
        }

        return self::contraste($colores['text'], $colores['background']) >= 4.5
            && self::contraste($colores['text'], $colores['surface']) >= 4.5;
    }

    public static function validar(array $colores): void
    {
        if (! self::legible($colores)) {
            throw ValidationException::withMessages(['colors.text' => 'El texto debe tener un contraste mínimo de 4.5:1 con el fondo y los paneles. Ajusta esos colores antes de guardar.']);
        }
    }

    public static function preferencia($usuario): array
    {
        $guardada = $usuario?->apariencia;
        $mode = in_array($guardada['mode'] ?? null, ['light', 'dark', 'custom'], true) ? $guardada['mode'] : 'light';
        $colors = $guardada['colors'] ?? self::PALETAS['light'];
        if (! is_array($colors) || ! self::legible($colors)) {
            $colors = self::PALETAS['light'];
        }

        return compact('mode', 'colors');
    }
}
