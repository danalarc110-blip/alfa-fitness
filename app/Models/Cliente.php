<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Cliente extends Authenticatable
{
    use Notifiable;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'correo',
        'password',
        'google_id',
        'avatar',
        'activo',
        'color_acento',
        'avatar_piel',
        'avatar_cabello',
        'avatar_barba',
        'avatar_atuendo',
        'avatar_color_atuendo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
