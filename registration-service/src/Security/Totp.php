<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Time-based one-time passwords (RFC 6238) for authenticator apps.
 *
 * Six digits, 30-second steps, HMAC-SHA1: the defaults every common
 * authenticator app supports. One step either side is accepted to allow
 * for clock drift. SMS codes are deliberately not supported.
 */
final class Totp
{
    private const STEP = 30;
    private const DIGITS = 6;
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::BASE32[bindec(str_pad($chunk, 5, '0'))];
        }

        return $secret;
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function currentStep(?int $time = null): int
    {
        return intdiv($time ?? time(), self::STEP);
    }

    /**
     * Returns the matching time step, or null. Steps at or before
     * $lastUsedStep are refused so a code cannot be replayed.
     */
    public static function verify(string $secret, string $code, int $lastUsedStep = 0, ?int $time = null): ?int
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $now = self::currentStep($time);
        foreach ([$now, $now - 1, $now + 1] as $step) {
            if ($step > $lastUsedStep && hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function provisioningUri(string $secret, string $account): string
    {
        $issuer = 'MFA&IC registry prototype';

        return sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30', rawurlencode($issuer), rawurlencode($account), $secret, rawurlencode($issuer));
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $value = strpos(self::BASE32, $char);
            if ($value === false) {
                throw new \InvalidArgumentException('Invalid base32 secret.');
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
