<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum PropertyType: string
{
    case AllProperties = '0';
    case AllDwellings = '1';
    case SingleFamilyHouses = '2';
    case Detached = '3';
    case Terraced = '4';
    case Large = '5';
    case MediumSized = '6';
    case Small = '7';
    case Flats = '8';
    case MultiDwelling = '9';
    case LandAllPurposes = 'K';
    case LandResidential = 'L';
    case Mixed = 'N';
    case NonHoliday = 'R';
    case BigFlats = 'S';

    private const LABELS = [
        '0' => 'All properties',
        '1' => 'All types of dwellings',
        '2' => 'Single-family houses',
        '3' => 'Single-family houses - detached',
        '4' => 'Single-family houses - terraced',
        '5' => 'Single-family houses - large',
        '6' => 'Single-family houses - medium sized',
        '7' => 'Single-family houses - small',
        '8' => 'Flats',
        '9' => 'Multi-dwelling buildings',
        'K' => 'Land for all purposes',
        'L' => 'Land for residential',
        'N' => 'Mixed (residential and non-residential)',
        'R' => 'All types of non-holidays dwellings',
        'S' => 'Big flats',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value];
    }
}
