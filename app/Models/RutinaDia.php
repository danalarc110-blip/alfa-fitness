<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RutinaDia extends Model
{
    protected $fillable = [
        'rutina_id',
        'orden',
        'titulo',
        'duracion_estimada_min',
        'duracion_estimada_max',
    ];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class)->orderBy('orden');
    }

    public function getEtiquetaAttribute(): string
    {
        return 'Día '.$this->orden;
    }
}
