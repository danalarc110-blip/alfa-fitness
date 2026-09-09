<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membresia extends Model
{
    protected $fillable = ['cliente_id', 'solicitud_id', 'plan', 'importe', 'inicio', 'fin', 'cancelada', 'activada_por'];

    protected function casts(): array
    {
        return ['inicio' => 'date', 'fin' => 'date', 'importe' => 'decimal:2', 'cancelada' => 'boolean'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function solicitud(): BelongsTo { return $this->belongsTo(SolicitudMembresia::class); }
    public function activadora(): BelongsTo { return $this->belongsTo(User::class, 'activada_por'); }
    public function pago() { return $this->hasOne(PagoMembresia::class); }

    public function getEstadoAttribute(): string
    {
        if ($this->cancelada) return 'Cancelada';
        if ($this->inicio->isAfter(today())) return 'Programada';
        return $this->fin->isBefore(today()) ? 'Vencida' : 'Vigente';
    }
}
