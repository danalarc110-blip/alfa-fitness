<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionEntrenamiento extends Model
{
    use HasFactory;

    protected $table = 'sesiones_entrenamiento';

    protected $fillable = [
        'user_id',
        'user_type',
        'rutina_id',
        'rutina_nombre',
        'dia_id',
        'dia_titulo',
        'iniciado_en',
        'finalizado_en',
        'duracion_segundos',
        'series_completadas',
        'total_series',
        'estado',
        'notas',
        'sesion_uuid',
    ];

    protected $casts = [
        'iniciado_en' => 'datetime',
        'finalizado_en' => 'datetime',
        'duracion_segundos' => 'integer',
        'series_completadas' => 'integer',
        'total_series' => 'integer',
    ];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class, 'rutina_id');
    }

    public function dia(): BelongsTo
    {
        return $this->belongsTo(RutinaDia::class, 'dia_id');
    }

    public function scopeDeUsuario(Builder $query, string $userType, int $userId): Builder
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }

    public function getDuracionFormateadaAttribute(): string
    {
        $segundos = $this->duracion_segundos ?? 0;
        if ($segundos <= 0) {
            return '< 1 min';
        }
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);

        if ($horas > 0) {
            return "{$horas}h {$minutos}m";
        }

        return max(1, $minutos).' min';
    }

    public function getPorcentajeCompletadoAttribute(): int
    {
        if ($this->total_series <= 0) {
            return 100;
        }

        return (int) round(($this->series_completadas / $this->total_series) * 100);
    }
}
