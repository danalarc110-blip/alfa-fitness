<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'precio',
        'imagen',
        'categoria',
        'stock',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return '$'.number_format((float) $this->precio, 2);
    }

    public function getImagenUrlAttribute(): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        if (filter_var($this->imagen, FILTER_VALIDATE_URL) || str_starts_with($this->imagen, 'http://') || str_starts_with($this->imagen, 'https://')) {
            return $this->imagen;
        }

        return asset('images/productos/'.$this->imagen);
    }
}
