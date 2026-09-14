<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $fillable = [
        'cliente_id',
        'registrado_por',
        'salida_registrada_por',
        'fecha_hora',
        'fecha_salida',
        'tipo_acceso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'fecha_salida' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function registradorSalida(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salida_registrada_por');
    }

    public function getDuracionAttribute(): ?string
    {
        if (! $this->fecha_salida) {
            return null;
        }
        $minutos = max(0, (int) $this->fecha_hora->diffInMinutes($this->fecha_salida));

        return intdiv($minutos, 60).'h '.($minutos % 60).'m';
    }
}
