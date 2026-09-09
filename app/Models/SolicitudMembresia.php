<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolicitudMembresia extends Model
{
    protected $table = 'solicitudes_membresia';
    protected $fillable = ['cliente_id', 'plan_id', 'plan_nombre', 'precio_acordado', 'duracion_dias', 'condiciones', 'estado', 'resuelta_en', 'resuelta_por'];
    protected function casts(): array { return ['precio_acordado' => 'decimal:2', 'duracion_dias' => 'integer', 'resuelta_en' => 'datetime']; }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function plan(): BelongsTo { return $this->belongsTo(PlanMembresia::class, 'plan_id'); }
    public function membresia(): HasOne { return $this->hasOne(Membresia::class, 'solicitud_id'); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'resuelta_por'); }
}
