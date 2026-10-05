<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionEntrenador extends Model
{
    protected $table = 'sesiones_entrenador';

    protected $fillable = ['cliente_id', 'entrenador_id', 'registrado_por', 'fecha_inicio', 'fecha_fin', 'estado', 'notas'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function entrenador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }
}
