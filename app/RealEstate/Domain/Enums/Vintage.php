<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum Vintage: string
{
    case All = '0';
    case Existing = '1';
    case New = '2';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All',
            self::Existing => 'Existing',
            self::New => 'New',
        };
    }
}
