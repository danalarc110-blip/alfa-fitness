<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class Invitaciones
{
    public function enviar(User $usuario): bool
    {
        if (! $usuario->activo || $usuario->password_establecida) {
            return false;
        }
        try {
            return Password::broker('users')->sendResetLink(['email' => $usuario->email]) === Password::RESET_LINK_SENT;
        } catch (\Throwable $error) {
            // Mail transport exceptions can include credentials or reset links.
            Log::warning('No se pudo enviar una invitación.', ['exception' => get_class($error)]);

            return false;
        }
    }
}
