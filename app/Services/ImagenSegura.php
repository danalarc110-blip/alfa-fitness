<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImagenSegura
{
    public function guardar(UploadedFile $archivo, string $directorio, string $prefijo): string
    {
        $destino = public_path('images/'.$directorio);
        if (! is_dir($destino)) mkdir($destino, 0755, true);
        $extension = match ($archivo->getMimeType()) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            default => throw new \InvalidArgumentException('Tipo de imagen no permitido.'),
        };
        $nombre = $prefijo.'_'.Str::uuid().'.'.$extension;
        $ruta = $destino.DIRECTORY_SEPARATOR.$nombre;

        // Re-encode when GD is available: strips metadata and limits transfer size without a new dependency.
        $origen = match ($extension) {
            'jpg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($archivo->getRealPath()) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($archivo->getRealPath()) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($archivo->getRealPath()) : false,
        };
        if ($origen) {
            $ancho = imagesx($origen); $alto = imagesy($origen); $maximo = 1600;
            $escala = min(1, $maximo / max($ancho, $alto));
            $salida = imagecreatetruecolor((int) round($ancho * $escala), (int) round($alto * $escala));
            if (in_array($extension, ['png', 'webp'], true)) { imagealphablending($salida, false); imagesavealpha($salida, true); }
            imagecopyresampled($salida, $origen, 0, 0, 0, 0, imagesx($salida), imagesy($salida), $ancho, $alto);
            match ($extension) { 'jpg' => imagejpeg($salida, $ruta, 82), 'png' => imagepng($salida, $ruta, 8), 'webp' => imagewebp($salida, $ruta, 82) };
            imagedestroy($origen); imagedestroy($salida);
        } else {
            $archivo->move($destino, $nombre);
        }
        return $nombre;
    }

    public function eliminar(?string $nombre, string $directorio, string $prefijo): void
    {
        if (! $nombre || basename($nombre) !== $nombre || ! str_starts_with($nombre, $prefijo.'_')) return;
        $ruta = public_path('images/'.$directorio.'/'.$nombre);
        if (is_file($ruta)) @unlink($ruta);
    }
}
