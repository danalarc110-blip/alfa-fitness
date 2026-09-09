<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanMembresia extends Model
{
    protected $table = 'planes_membresia';
    protected $fillable = ['nombre', 'precio', 'duracion_dias', 'condiciones', 'activo'];
    protected function casts(): array { return ['precio' => 'decimal:2', 'duracion_dias' => 'integer', 'activo' => 'boolean']; }
}
