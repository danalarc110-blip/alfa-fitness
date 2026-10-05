<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDatos extends Model
{
    protected $table = 'solicitudes_datos';

    protected $fillable = ['nombre', 'correo', 'tipo', 'detalle'];

    protected function casts(): array
    {
        return ['resuelta_en' => 'datetime'];
    }
}
