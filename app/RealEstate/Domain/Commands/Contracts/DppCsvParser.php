<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

use App\RealEstate\Domain\Data\DppObservationData;
use App\RealEstate\Domain\Data\DppSeriesData;
use Generator;

interface DppCsvParser
{
    /**
     * Parse DPP CSV file and yield observation DTOs.
     *
     * @return Generator<int, DppObservationData>
     */
    public function parse(string $filePath): Generator;

    /**
     * Extract metadata (series info) from CSV.
     *
     * @return array<string, DppSeriesData> keyed by dimension key
     */
    public function parseMetadata(string $filePath): array;
}
