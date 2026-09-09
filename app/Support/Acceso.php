<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/** One permission matrix for routes, controllers and navigation. Unknown roles fail closed. */
final class Acceso
{
    public const ROLES = ['Administrador', 'Secretaria', 'Entrenador'];
    public const ROLES_ASIGNABLES = ['Secretaria', 'Entrenador'];

    public static function permite(?Authenticatable $user, string $permiso): bool
    {
        if (! $user || ! $user->activo) return false;
        if ($user instanceof Cliente) return in_array($permiso, ['membresias', 'progreso'], true);
        if (! $user instanceof User) return false;
        $permisos = match ($user->rol) {
            'Administrador' => ['administrar', 'inventario', 'clientes'],
            'Secretaria' => ['asistencia', 'membresias', 'operaciones', 'inventario', 'clientes'],
            default => [],
        };
        return in_array($permiso, $permisos, true);
    }
}
