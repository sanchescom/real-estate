<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum UnitMeasure: string
{
    case Index = '628';
    case YearOnYear = '771';

    public function label(): string
    {
        return match ($this) {
            self::Index => 'index',
            self::YearOnYear => 'yoy',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Index => 'Index, 2010 = 100',
            self::YearOnYear => 'Year-on-year changes, in per cent',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'index' => self::Index,
            'yoy' => self::YearOnYear,
            default => throw new \ValueError("Invalid unit measure label: {$label}"),
        };
    }
}
