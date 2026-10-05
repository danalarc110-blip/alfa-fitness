<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SinDatosTarjeta implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        if (preg_match('/\b(?:CVV|CVC|PIN)\s*[:=]\s*\d{3,6}\b/i', $value)) {
            $fail('No escribas datos de tarjeta, CVV ni PIN. Registra únicamente el método de pago.');

            return;
        }
        preg_match_all('/(?<!\d)(?:\d[ -]?){12,18}\d(?!\d)/', $value, $matches);
        foreach ($matches[0] as $candidate) {
            $number = preg_replace('/\D/', '', $candidate);
            $sum = 0;
            foreach (str_split(strrev($number)) as $index => $digit) {
                $n = (int) $digit * ($index % 2 ? 2 : 1);
                $sum += $n > 9 ? $n - 9 : $n;
            }
            if ($sum % 10 === 0) {
                $fail('La referencia o nota parece contener un número de tarjeta. Retíralo y registra solo el método de pago.');

                return;
            }
        }
    }
}
