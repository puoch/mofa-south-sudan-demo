<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Registration\ReferenceGenerator;
use PHPUnit\Framework\TestCase;

final class ReferenceGeneratorTest extends TestCase
{
    public function testFormatAndCheckCharacter(): void
    {
        $generator = new ReferenceGenerator();
        for ($i = 0; $i < 200; ++$i) {
            $reference = $generator->generate();
            self::assertMatchesRegularExpression('/^SSD(-[0-9A-HJKMNP-TV-Z]{4}){4}$/', $reference);
            self::assertTrue(ReferenceGenerator::isValid($reference), $reference);
        }
    }

    public function testReferencesAreNotRepeated(): void
    {
        $generator = new ReferenceGenerator();
        $references = array_map(fn () => $generator->generate(), range(1, 1000));
        self::assertCount(1000, array_unique($references));
    }

    public function testAnySingleMistypedCharacterIsDetected(): void
    {
        $reference = (new ReferenceGenerator())->generate();
        $body = str_replace(['SSD-', '-'], '', $reference);
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        for ($position = 0; $position < 16; ++$position) {
            foreach (str_split($alphabet) as $char) {
                if ($char === $body[$position]) {
                    continue;
                }
                $typo = substr_replace($body, $char, $position, 1);
                self::assertFalse(ReferenceGenerator::isValid('SSD'.$typo), 'SSD'.$typo);
            }
        }
    }

    public function testNormalizationAcceptsLowercaseAndLookAlikes(): void
    {
        $reference = (new ReferenceGenerator())->generate();
        self::assertTrue(ReferenceGenerator::isValid(strtolower(str_replace('-', ' ', $reference))));
        self::assertFalse(ReferenceGenerator::isValid('SSD-1234'));
        self::assertFalse(ReferenceGenerator::isValid('XYZ-0000-0000-0000-0000'));
    }
}
