<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function actual(): array
    {
        // If both guards ever coexist, choose the least-privileged client identity.
        $guard = auth('cliente')->check() ? 'cliente' : 'web';
        return ['guard' => $guard, 'user' => auth($guard)->user()];
    }

    protected function nombreActual($guard, $user): string
    {
        return $guard === 'web' ? $user->name : $user->nombre;
    }
}
