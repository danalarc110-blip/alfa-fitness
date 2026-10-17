<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrenadorCalificacion extends Model
{
    protected $table = 'entrenador_calificaciones';

    protected $fillable = ['entrenador_id', 'cliente_id', 'estrellas'];

    protected function casts(): array
    {
        return ['estrellas' => 'integer'];
    }

    public function entrenador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
