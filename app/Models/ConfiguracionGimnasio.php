<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionGimnasio extends Model
{
    protected $table = 'configuracion_gimnasio';

    protected $fillable = ['datos'];

    protected function casts(): array
    {
        return ['datos' => 'array'];
    }
}
