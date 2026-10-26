<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $attributes = ['pago_simulado' => false];

    protected $fillable = [
        'venta_uuid',
        'user_id',
        'cliente_id',
        'total',
        'metodo_pago',
        'notas',
        'pago_simulado',
        'efectivo_recibido',
        'cambio',
        'referencia_pago',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'pago_simulado' => 'boolean',
            'efectivo_recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
        ];
    }

    public function scopeReales(Builder $query): Builder
    {
        return $query->where('pago_simulado', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
