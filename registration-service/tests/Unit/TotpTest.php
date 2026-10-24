<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Security\Totp;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    // RFC 6238 appendix B secret "12345678901234567890" in base32.
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // gitleaks:allow (public RFC test vector)

    public function testRfc6238Vectors(): void
    {
        // The RFC lists 8-digit codes; six digits are their last six.
        self::assertSame('287082', Totp::code(self::SECRET, Totp::currentStep(59)));
        self::assertSame('081804', Totp::code(self::SECRET, Totp::currentStep(1111111109)));
        self::assertSame('005924', Totp::code(self::SECRET, Totp::currentStep(1234567890)));
    }

    public function testVerifyAllowsOneStepOfDriftAndRefusesReplay(): void
    {
        $time = 1234567890;
        $step = Totp::currentStep($time);
        self::assertSame($step, Totp::verify(self::SECRET, Totp::code(self::SECRET, $step), 0, $time));
        self::assertSame($step - 1, Totp::verify(self::SECRET, Totp::code(self::SECRET, $step - 1), 0, $time));
        self::assertNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $step - 2), 0, $time));
        self::assertNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $step), $step, $time), 'replayed code');
        self::assertNull(Totp::verify(self::SECRET, 'abcdef', 0, $time));
    }

    public function testGeneratedSecretsAreBase32AndUnique(): void
    {
        $a = Totp::generateSecret();
        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        self::assertNotSame($a, Totp::generateSecret());
        self::assertMatchesRegularExpression('/^\d{6}$/', Totp::code($a, 1));
    }
}
