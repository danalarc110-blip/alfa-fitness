<?php

namespace App\Services;

/** RFC 6238, SHA-1, 30 seconds, six digits. No secrets are sent to third parties. */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function secret(): string
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::ALPHABET[bindec($chunk)];
        }

        return $secret;
    }

    public function code(string $secret, int $step, int $digits = 6): string
    {
        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                throw new \InvalidArgumentException('Secreto TOTP inválido.');
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $key .= chr(bindec($chunk));
            }
        }
        $hash = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step % 4294967296), $key, true);
        $offset = ord($hash[19]) & 15;
        $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($number % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function matchingStep(string $secret, string $code, ?int $lastStep = null): ?int
    {
        if (! preg_match('/^[0-9]{6}$/D', $code)) {
            return null;
        }
        $now = intdiv(now()->timestamp, 30);
        foreach ([$now, $now - 1, $now + 1] as $step) {
            if ($step >= 0 && ($lastStep === null || $step > $lastStep) && hash_equals($this->code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }
}
