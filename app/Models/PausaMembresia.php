<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PausaMembresia extends Model
{
    protected $table = 'pausas_membresia';

    protected $fillable = [
        'membresia_id',
        'cliente_id',
        'dias',
        'motivo',
        'estado',
        'inicio_pausa',
        'fin_pausa_estimada',
        'fecha_reanudacion',
        'aprobada_por',
    ];

    protected function casts(): array
    {
        return [
            'dias' => 'integer',
            'inicio_pausa' => 'date',
            'fin_pausa_estimada' => 'date',
            'fecha_reanudacion' => 'date',
        ];
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    /**
     * Determina si la pausa está actualmente en vigencia.
     */
    public function estaVigenteHoy(): bool
    {
        return $this->estado === 'aprobada'
            && today()->gte($this->inicio_pausa)
            && today()->lte($this->fin_pausa_estimada);
    }
}
