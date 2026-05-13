<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum ValueType: string
{
    case Nominal = 'N';
    case Real = 'R';

    public function label(): string
    {
        return match ($this) {
            self::Nominal => 'nominal',
            self::Real => 'real',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'nominal' => self::Nominal,
            'real' => self::Real,
            default => throw new \ValueError("Invalid value type label: {$label}"),
        };
    }
}
