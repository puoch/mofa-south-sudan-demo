<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Security\FieldCipher;
use PHPUnit\Framework\TestCase;

final class FieldCipherTest extends TestCase
{
    private function cipher(): FieldCipher
    {
        return new FieldCipher(base64_encode(str_repeat('a', 32)), base64_encode(str_repeat('b', 32)));
    }

    public function testRoundTripWithoutPlaintextInCiphertext(): void
    {
        $cipher = $this->cipher();
        $sealed = $cipher->encrypt('SYN123456', 'passport_number');
        self::assertStringStartsWith('v1:', $sealed);
        self::assertStringNotContainsString('SYN123456', $sealed);
        self::assertSame('SYN123456', $cipher->decrypt($sealed, 'passport_number'));
    }

    public function testSameValueEncryptsDifferentlyEachTime(): void
    {
        $cipher = $this->cipher();
        self::assertNotSame($cipher->encrypt('SYN123456', 'passport_number'), $cipher->encrypt('SYN123456', 'passport_number'));
    }

    public function testCiphertextCannotBeMovedToAnotherField(): void
    {
        $cipher = $this->cipher();
        $sealed = $cipher->encrypt('1990-01-01', 'date_of_birth');
        $this->expectException(\UnexpectedValueException::class);
        $cipher->decrypt($sealed, 'passport_expiry');
    }

    public function testTamperedCiphertextIsRejected(): void
    {
        $cipher = $this->cipher();
        $sealed = $cipher->encrypt('SYN123456', 'passport_number');
        $raw = base64_decode(substr($sealed, 3));
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 1);
        $this->expectException(\UnexpectedValueException::class);
        $cipher->decrypt('v1:'.base64_encode($raw), 'passport_number');
    }

    public function testBlindIndexIsStableAndKeyed(): void
    {
        $cipher = $this->cipher();
        $other = new FieldCipher(base64_encode(str_repeat('a', 32)), base64_encode(str_repeat('c', 32)));
        self::assertSame($cipher->blindIndex('SYN123456', 'passport_number'), $cipher->blindIndex('SYN123456', 'passport_number'));
        self::assertNotSame($cipher->blindIndex('SYN123456', 'passport_number'), $other->blindIndex('SYN123456', 'passport_number'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $cipher->blindIndex('SYN123456', 'passport_number'));
    }

    public function testRejectsMissingOrReusedKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FieldCipher('', base64_encode(str_repeat('b', 32)));
    }

    public function testRejectsSameKeyForBothPurposes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FieldCipher(base64_encode(str_repeat('a', 32)), base64_encode(str_repeat('a', 32)));
    }
}
