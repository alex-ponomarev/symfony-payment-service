<?php

namespace App\Enum;

//Сделал налоговые константы в перечислении, т.к. они редко изменяются и часто запрашиваются - это дешевле здесь, чем в БД
enum Tax: string
{
    case Germany = "DE";
    case Italy = "IT";
    case Greece = "GR";
    case France = "FR";

    public static function isValidTaxNumber(string $taxNumber): bool
    {
        return self::matchingTax($taxNumber) !== null;
    }

    public static function rateByTaxNumber(string $taxNumber): ?int
    {
        return self::matchingTax($taxNumber)?->taxRatePercent();
    }

    private function taxRatePercent(): int
    {
        return match ($this) {
            self::Germany => 19,
            self::Italy => 22,
            self::Greece => 24,
            self::France => 20,
        };
    }

    private function taxNumberPattern(): string
    {
        return match ($this) {
            self::Germany => "~\\ADE[0-9]{9}\\z~",
            self::Italy => "~\\AIT[0-9]{11}\\z~",
            self::Greece => "~\\AGR[0-9]{9}\\z~",
            self::France => "~\\AFR[A-Z]{2}[0-9]{9}\\z~",
        };
    }

    private function acceptsTaxNumber(string $taxNumber): bool
    {
        return preg_match($this->taxNumberPattern(), $taxNumber) === 1;
    }

    private static function matchingTax(string $taxNumber): ?self
    {
        $country = self::tryFrom(substr($taxNumber, 0, 2));

        if ($country === null || !$country->acceptsTaxNumber($taxNumber)) {
            return null;
        }

        return $country;
    }
}
