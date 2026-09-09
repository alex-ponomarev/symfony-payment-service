<?php

namespace App\Tests\Enum;

use App\Enum\Tax;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TaxTest extends TestCase
{
    #[DataProvider('validTaxNumberProvider')]
    public function testValidTaxNumberHasExpectedRate(string $taxNumber, int $expectedRate): void
    {
        self::assertTrue(Tax::isValidTaxNumber($taxNumber));
        self::assertSame($expectedRate, Tax::rateByTaxNumber($taxNumber));
    }

    public static function validTaxNumberProvider(): iterable
    {
        yield 'Germany' => ['DE123456789', 19];
        yield 'Italy' => ['IT12345678900', 22];
        yield 'Greece' => ['GR123456789', 24];
        yield 'France' => ['FRAB123456789', 20];
    }

    #[DataProvider('invalidTaxNumberProvider')]
    public function testInvalidTaxNumberHasNoRate(string $taxNumber): void
    {
        self::assertFalse(Tax::isValidTaxNumber($taxNumber));
        self::assertNull(Tax::rateByTaxNumber($taxNumber));
    }

    public static function invalidTaxNumberProvider(): iterable
    {
        yield 'empty value' => [''];
        yield 'unknown country' => ['ES123456789'];
        yield 'lowercase country prefix' => ['de123456789'];
        yield 'leading whitespace' => [' DE123456789'];
        yield 'trailing whitespace' => ['DE123456789 '];

        yield 'Germany too short' => ['DE12345678'];
        yield 'Germany too long' => ['DE1234567890'];
        yield 'Germany contains a letter' => ['DE12345678A'];

        yield 'Italy too short' => ['IT1234567890'];
        yield 'Italy too long' => ['IT123456789012'];
        yield 'Italy contains a letter' => ['IT1234567890A'];

        yield 'Greece too short' => ['GR12345678'];
        yield 'Greece too long' => ['GR1234567890'];
        yield 'Greece contains a letter' => ['GR12345678A'];

        yield 'France has one letter' => ['FRA123456789'];
        yield 'France has three letters' => ['FRABC123456789'];
        yield 'France has digits instead of letters' => ['FR12123456789'];
        yield 'France has lowercase letters' => ['FRab123456789'];
        yield 'France has too few digits' => ['FRAB12345678'];
        yield 'France has too many digits' => ['FRAB1234567890'];
    }
}
