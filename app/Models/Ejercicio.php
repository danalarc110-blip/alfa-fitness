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
        'calificacion_promedio',
        'total_calificaciones',
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

    public function calificaciones(): HasMany
    {
        return $this->hasMany(EjercicioCalificacion::class);
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    /**
     * URL pública de la imagen del ejercicio.
     * Busca en public/images/ejercicios/ y fallback en public/images/.
     */
    public function getImagenUrlAttribute(): string
    {
        $archivo = $this->imagen ?: mb_strtolower($this->nombre).'.png';

        if (file_exists(public_path('images/ejercicios/'.$archivo))) {
            return asset('images/ejercicios/'.$archivo);
        }

        if (file_exists(public_path('images/'.$archivo))) {
            return asset('images/'.$archivo);
        }

        // Intento con variaciones de extensión (.jpg, .png, .jpeg, .webp)
        $baseName = pathinfo($archivo, PATHINFO_FILENAME);
        foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'PNG', 'JPEG'] as $ext) {
            $candidate = $baseName.'.'.$ext;
            if (file_exists(public_path('images/ejercicios/'.$candidate))) {
                return asset('images/ejercicios/'.$candidate);
            }
            if (file_exists(public_path('images/'.$candidate))) {
                return asset('images/'.$candidate);
            }
        }

        return asset('images/ejercicios/'.$archivo);
    }

    /**
     * URL pública de la imagen "músculos que entrena" del ejercicio.
     */
    public function getImagenMusculosUrlAttribute(): string
    {
        $archivo = $this->imagen_musculos ?: mb_strtolower($this->nombre).' musculos que entrena.png';

        if (file_exists(public_path('images/ejercicios/'.$archivo))) {
            return asset('images/ejercicios/'.$archivo);
        }

        if (file_exists(public_path('images/'.$archivo))) {
            return asset('images/'.$archivo);
        }

        $baseName = pathinfo($archivo, PATHINFO_FILENAME);
        foreach (['png', 'jpg', 'jpeg', 'webp', 'PNG', 'JPG'] as $ext) {
            $candidate = $baseName.'.'.$ext;
            if (file_exists(public_path('images/ejercicios/'.$candidate))) {
                return asset('images/ejercicios/'.$candidate);
            }
            if (file_exists(public_path('images/'.$candidate))) {
                return asset('images/'.$candidate);
            }
        }

        return asset('images/ejercicios/'.$archivo);
    }

    /**
     * True si el archivo de imagen del ejercicio ya existe en disco.
     */
    public function getTieneImagenAttribute(): bool
    {
        $archivo = $this->imagen ?: mb_strtolower($this->nombre).'.png';

        if (file_exists(public_path('images/ejercicios/'.$archivo)) || file_exists(public_path('images/'.$archivo))) {
            return true;
        }

        $baseName = pathinfo($archivo, PATHINFO_FILENAME);
        foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'PNG', 'JPEG'] as $ext) {
            $candidate = $baseName.'.'.$ext;
            if (file_exists(public_path('images/ejercicios/'.$candidate)) || file_exists(public_path('images/'.$candidate))) {
                return true;
            }
        }

        return false;
    }

    public function getTieneImagenMusculosAttribute(): bool
    {
        $archivo = $this->imagen_musculos ?: mb_strtolower($this->nombre).' musculos que entrena.png';

        if (file_exists(public_path('images/ejercicios/'.$archivo)) || file_exists(public_path('images/'.$archivo))) {
            return true;
        }

        $baseName = pathinfo($archivo, PATHINFO_FILENAME);
        foreach (['png', 'jpg', 'jpeg', 'webp', 'PNG', 'JPG'] as $ext) {
            $candidate = $baseName.'.'.$ext;
            if (file_exists(public_path('images/ejercicios/'.$candidate)) || file_exists(public_path('images/'.$candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calificación promedio de 1.0 a 5.0 (de la semana o global).
     */
    public function getCalificacionPromedioAttribute(): float
    {
        $promedio = $this->relationLoaded('calificaciones')
            ? $this->calificaciones->avg('estrellas')
            : $this->calificaciones()->avg('estrellas');

        return $promedio ? round((float) $promedio, 1) : 5.0; // Valor predeterminado de popularidad 5.0
    }

    /**
     * Total de votos recibidos.
     */
    public function getTotalCalificacionesAttribute(): int
    {
        return $this->relationLoaded('calificaciones')
            ? $this->calificaciones->count()
            : $this->calificaciones()->count();
    }

    /**
     * Obtiene la calificación que dio un usuario específico.
     */
    public function calificacionDeUsuario(string $guard, int $userId): ?int
    {
        $cal = $this->calificaciones()
            ->where('user_type', $guard)
            ->where('user_id', $userId)
            ->first();

        return $cal ? $cal->estrellas : null;
    }
}
