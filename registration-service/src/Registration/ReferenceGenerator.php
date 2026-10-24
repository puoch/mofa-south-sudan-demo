<?php

declare(strict_types=1);

namespace App\Registration;

/**
 * Generates registration reference numbers such as SSD-7K4M-QX2P-9TRA-B3H6.
 *
 * Fifteen characters (75 bits) come from the system's secure random source,
 * in Crockford base32 so that I, L, O and U never appear. The last character
 * is a Luhn mod 32 check character, which catches any single mistyped
 * character and most swapped neighbours. References are never sequential and
 * do not encode any personal data or the date.
 */
final class ReferenceGenerator
{
    public const PREFIX = 'SSD';
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const RANDOM_LENGTH = 15;

    public function generate(): string
    {
        $chars = '';
        for ($i = 0; $i < self::RANDOM_LENGTH; ++$i) {
            $chars .= self::ALPHABET[random_int(0, 31)];
        }
        $chars .= self::checkCharacter($chars);

        return self::PREFIX.'-'.implode('-', str_split($chars, 4));
    }

    public static function isValid(string $reference): bool
    {
        $normalized = self::normalize($reference);
        if (!preg_match('/^'.self::PREFIX.'[0-9A-HJKMNP-TV-Z]{16}$/', $normalized)) {
            return false;
        }
        $body = substr($normalized, strlen(self::PREFIX));

        return self::checkCharacter(substr($body, 0, -1)) === substr($body, -1);
    }

    /**
     * Uppercases, drops separators and maps look-alike characters.
     */
    public static function normalize(string $reference): string
    {
        $reference = strtoupper(preg_replace('/[\s-]+/', '', $reference));

        return strtr($reference, ['O' => '0', 'I' => '1', 'L' => '1']);
    }

    private static function checkCharacter(string $chars): string
    {
        $sum = 0;
        $double = true;
        for ($i = strlen($chars) - 1; $i >= 0; --$i) {
            $value = strpos(self::ALPHABET, $chars[$i]);
            if ($double) {
                $value *= 2;
                $value = intdiv($value, 32) + $value % 32;
            }
            $sum += $value;
            $double = !$double;
        }

        return self::ALPHABET[(32 - $sum % 32) % 32];
    }
}
