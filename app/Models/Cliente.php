<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'google_id',
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

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
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

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function solicitudesMembresia(): HasMany
    {
        return $this->hasMany(SolicitudMembresia::class);
    }

    public function ultimaMembresia(): HasOne
    {
        return $this->hasOne(Membresia::class)->ofMany(['fin' => 'max', 'id' => 'max'], fn ($q) => $q->where('cancelada', false));
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

        if (is_file(public_path('images/avatars/'.$this->avatar))) {
            return asset('images/avatars/'.$this->avatar);
        }

        return null;
    }
}
