<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Enums;

enum CoveredArea: string
{
    case WholeCountry = '0';
    case ExcludingCapital = '1';
    case Capital = '2';
    case CapitalAndSuburbs = '3';
    case BigCities = '4';
    case ABigCity = '5';
    case BigAndMediumCities = '6';
    case SmallCities = '8';
    case UrbanAreas = '9';
    case RuralArea = 'A';
    case ChangingComposition = 'B';
    case Region1 = 'R';
    case Region2 = 'S';

    private const array LABELS = [
        '0' => 'Whole country',
        '1' => 'Whole country excluding capital city',
        '2' => 'Capital city/biggest city/financial center',
        '3' => 'Capital/biggest city/financial center and suburbs',
        '4' => 'Big cities',
        '5' => 'A big city',
        '6' => 'Big & medium cities',
        '8' => 'Small cities',
        '9' => 'Urban areas',
        'A' => 'Rural area',
        'B' => 'Changing composition',
        'R' => 'Region (1)',
        'S' => 'Region (2)',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value];
    }
}
