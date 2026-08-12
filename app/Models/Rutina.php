<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rutina extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'nombre',
        'objetivo',
        'nivel',
        'dias_por_semana',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    public function dias(): HasMany
    {
        return $this->hasMany(RutinaDia::class)->orderBy('orden');
    }

    /**
     * Filtra rutinas por dueño (empleado o cliente), sin necesidad de FK directa.
     */
    public function scopeDeUsuario($query, string $userType, int $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }

    public function totalEjercicios(): int
    {
        return $this->dias->sum(fn (RutinaDia $dia) => $dia->ejercicios->count());
    }
}
