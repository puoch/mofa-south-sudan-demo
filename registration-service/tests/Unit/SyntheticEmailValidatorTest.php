<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Validator\SyntheticEmailValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticEmailValidatorTest extends TestCase
{
    public static function domains(): array
    {
        return [
            ['example.org', true],
            ['mail.example.com', true],
            ['example.net', true],
            ['ministry.test', true],
            ['anything.example', true],
            ['gmail.com', false],
            ['example.org.evil.com', false],
            ['notexample.org', false],
            ['test', false],
            ['', false],
        ];
    }

    #[DataProvider('domains')]
    public function testReservedDomains(string $domain, bool $expected): void
    {
        self::assertSame($expected, SyntheticEmailValidator::isReserved($domain));
    }
}
