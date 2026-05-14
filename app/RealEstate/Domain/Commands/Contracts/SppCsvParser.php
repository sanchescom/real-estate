<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

use App\RealEstate\Domain\Data\SppObservationData;
use Generator;

interface SppCsvParser
{
    /**
     * Parse SPP CSV file and yield observation DTOs.
     *
     * @return Generator<int, SppObservationData>
     */
    public function parse(string $filePath): Generator;
}
