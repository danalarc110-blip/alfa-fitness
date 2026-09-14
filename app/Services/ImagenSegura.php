<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImagenSegura
{
    public function guardar(UploadedFile $archivo, string $directorio, string $prefijo): string
    {
        if (! in_array($directorio, ['avatars', 'productos'], true) || ! preg_match('/^[a-z0-9_]+$/D', $prefijo)) {
            throw new \InvalidArgumentException('Destino de imagen no permitido.');
        }
        $dimensiones = @getimagesize($archivo->getRealPath());
        if (! $dimensiones || $dimensiones[0] * $dimensiones[1] > 16777216) {
            throw ValidationException::withMessages([$directorio === 'avatars' ? 'avatar' : 'imagen' => 'La imagen debe tener como máximo 16 megapíxeles.']);
        }
        $destino = public_path('images/'.$directorio);
        if (! is_dir($destino)) {
            mkdir($destino, 0755, true);
        }
        $mime = $dimensiones['mime'] ?? null;
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || ! function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                $directorio === 'avatars' ? 'avatar' : 'imagen' => 'No se pudo procesar la imagen en un formato seguro.',
            ]);
        }
        $nombre = $prefijo.'_'.Str::uuid().'.webp';
        $ruta = $destino.DIRECTORY_SEPARATOR.$nombre;

        // Always re-encode: never publish original bytes or private EXIF metadata.
        $origen = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($archivo->getRealPath()) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($archivo->getRealPath()) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($archivo->getRealPath()) : false,
        };
        if ($origen) {
            $ancho = imagesx($origen);
            $alto = imagesy($origen);
            $maximo = $directorio === 'avatars' ? 640 : 1200;
            $escala = min(1, $maximo / max($ancho, $alto));
            $salida = imagecreatetruecolor(max(1, (int) round($ancho * $escala)), max(1, (int) round($alto * $escala)));
            imagealphablending($salida, false);
            imagesavealpha($salida, true);
            imagecopyresampled($salida, $origen, 0, 0, 0, 0, imagesx($salida), imagesy($salida), $ancho, $alto);
            $guardada = imagewebp($salida, $ruta, 82);
            imagedestroy($origen);
            imagedestroy($salida);
            if (! $guardada) {
                if (is_file($ruta)) {
                    unlink($ruta);
                }
                throw new \RuntimeException('No se pudo almacenar la imagen.');
            }
        } else {
            throw ValidationException::withMessages([$directorio === 'avatars' ? 'avatar' : 'imagen' => 'No se pudo procesar la imagen. Comprueba el archivo y que el servidor tenga GD habilitado.']);
        }

        return $nombre;
    }

    public function eliminar(?string $nombre, string $directorio, string $prefijo): void
    {
        if (! $nombre || basename($nombre) !== $nombre || ! str_starts_with($nombre, $prefijo.'_')) {
            return;
        }
        $ruta = public_path('images/'.$directorio.'/'.$nombre);
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
