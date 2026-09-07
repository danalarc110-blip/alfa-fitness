<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    protected $fillable = [
        'cliente_id',
        'ejercicio_id',
        'peso_kg',
        'repeticiones',
        'verificado',
        'verificado_por',
        'fecha_verificacion',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'peso_kg' => 'decimal:2',
            'repeticiones' => 'integer',
            'verificado' => 'boolean',
            'fecha_verificacion' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function getPesoFormateadoAttribute(): string
    {
        return rtrim(rtrim((string) $this->peso_kg, '0'), '.').' kg';
    }

    public function getVolumenAttribute(): float
    {
        return (float) $this->peso_kg * $this->repeticiones;
    }

    public function getNivelAttribute(): string
    {
        $volumen = $this->volumen;

        return match (true) {
            $volumen >= 600 => 'Avanzado',
            $volumen >= 300 => 'Intermedio',
            default => 'Inicial',
        };
    }
}
