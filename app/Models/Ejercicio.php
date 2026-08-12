<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ejercicio extends Model
{
    protected $appends = [
        'imagen_url',
        'imagen_musculos_url',
        'tiene_imagen',
        'tiene_imagen_musculos',
    ];

    protected $fillable = [
        'nombre',
        'grupo_muscular',
        'subgrupo',
        'imagen',
        'imagen_musculos',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function rutinaEjercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class);
    }

    /**
     * URL pública de la imagen del ejercicio.
     * Los archivos se guardan en public/images/ejercicios/{nombre}.png (todo en minúsculas).
     */
    public function getImagenUrlAttribute(): string
    {
        $archivo = $this->imagen ?: mb_strtolower($this->nombre).'.png';

        return asset('images/ejercicios/'.$archivo);
    }

    /**
     * URL pública de la imagen "músculos que entrena" del ejercicio.
     * Convención por defecto: public/images/ejercicios/{nombre} musculos que entrena.png
     */
    public function getImagenMusculosUrlAttribute(): string
    {
        $archivo = $this->imagen_musculos ?: mb_strtolower($this->nombre).' musculos que entrena.png';

        return asset('images/ejercicios/'.$archivo);
    }

    /**
     * True si el archivo de imagen del ejercicio ya existe en disco.
     */
    public function getTieneImagenAttribute(): bool
    {
        $archivo = $this->imagen ?: mb_strtolower($this->nombre).'.png';

        return file_exists(public_path('images/ejercicios/'.$archivo));
    }

    public function getTieneImagenMusculosAttribute(): bool
    {
        $archivo = $this->imagen_musculos ?: mb_strtolower($this->nombre).' musculos que entrena.png';

        return file_exists(public_path('images/ejercicios/'.$archivo));
    }
}
