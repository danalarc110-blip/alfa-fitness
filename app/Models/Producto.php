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
        if (! $this->imagen || basename($this->imagen) !== $this->imagen) {
            return null;
        }

        $optimized = pathinfo($this->imagen, PATHINFO_FILENAME).'.webp';
        if (is_file(public_path('images/productos/'.$optimized))) {
            return asset('images/productos/'.$optimized);
        }

        return asset('images/productos/'.$this->imagen);
    }
}
