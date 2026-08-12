<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutinaEjercicio extends Model
{
    protected $fillable = [
        'rutina_dia_id',
        'ejercicio_id',
        'orden',
        'series',
        'repeticiones',
        'peso',
        'descanso_segundos',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
        ];
    }

    public function dia(): BelongsTo
    {
        return $this->belongsTo(RutinaDia::class, 'rutina_dia_id');
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }

    public function getPesoFormateadoAttribute(): string
    {
        return $this->peso ? rtrim(rtrim((string) $this->peso, '0'), '.').' kg' : '—';
    }
}
