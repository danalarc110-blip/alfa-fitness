<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'password_establecida' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_establecida',
        'rol',
        'activo',
        'color_acento',
        'avatar_piel',
        'avatar_cabello',
        'avatar_barba',
        'avatar_atuendo',
        'avatar_color_atuendo',
        'avatar',
        'apariencia',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'password_establecida' => 'boolean',
            'apariencia' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->exists && $user->getOriginal('rol') === 'Administrador' && ($user->rol !== 'Administrador' || ! $user->activo)) {
                throw new \LogicException('El administrador unico no puede desactivarse ni cambiar de rol.');
            }
            $user->admin_key = $user->rol === 'Administrador' ? 'unico' : null;
        });
        static::deleting(function (User $user) {
            if ($user->rol === 'Administrador') {
                throw new \LogicException('La cuenta del administrador unico no se puede eliminar.');
            }
        });
    }

    public function asistenciasRegistradas(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'registrado_por');
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
