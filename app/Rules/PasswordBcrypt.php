<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordBcrypt implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        // Bcrypt only authenticates the first 72 bytes, not 72 Unicode characters.
        if (strlen($value) > 72) {
            $fail('La contraseña es demasiado larga: debe ocupar como máximo 72 bytes.');
        }
        if (str_contains($value, "\0")) {
            $fail('La contraseña no puede contener caracteres nulos.');
        }
    }
}
