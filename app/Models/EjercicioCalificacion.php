<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjercicioCalificacion extends Model
{
    protected $table = 'ejercicio_calificaciones';

    protected $fillable = [
        'ejercicio_id',
        'user_id',
        'user_type',
        'estrellas',
    ];

    protected function casts(): array
    {
        return [
            'estrellas' => 'integer',
        ];
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }
}
