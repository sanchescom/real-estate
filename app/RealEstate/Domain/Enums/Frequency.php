<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum Frequency: string
{
    case Quarterly = 'Q';
    case Annual = 'A';
    case Monthly = 'M';
    case HalfYearly = 'H';

    public function label(): string
    {
        return match ($this) {
            self::Quarterly => 'quarterly',
            self::Annual => 'annual',
            self::Monthly => 'monthly',
            self::HalfYearly => 'half-yearly',
        };
    }
}
