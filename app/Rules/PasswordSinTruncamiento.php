<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** bcrypt handles at most 72 bytes, not 72 Unicode characters. */
class PasswordSinTruncamiento implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) > 72) {
            $fail('La contraseña no debe superar 72 bytes. Usa una frase larga sin exceder ese límite.');
        }
    }
}
