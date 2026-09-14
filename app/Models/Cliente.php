<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'apariencia',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'baneado_en' => 'datetime',
            'apariencia' => 'array',
        ];
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class);
    }

    public function solicitudesMembresia(): HasMany
    {
        return $this->hasMany(SolicitudMembresia::class);
    }

    /** Keep profile images first-party so opening the app never contacts a tracking host. */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar || basename($this->avatar) !== $this->avatar) {
            return null;
        }

        $optimized = pathinfo($this->avatar, PATHINFO_FILENAME).'.webp';
        if (is_file(public_path('images/avatars/'.$optimized))) {
            return asset('images/avatars/'.$optimized);
        }

        return asset('images/avatars/'.$this->avatar);
    }
}
