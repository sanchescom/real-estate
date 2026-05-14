<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

use App\RealEstate\Domain\Data\DppSeriesData;

interface DppDataStore
{
    /**
     * Upsert series from metadata and return the dimension_key => DB id map.
     *
     * @param  array<string, DppSeriesData>  $metadata
     * @return array<string, int>
     */
    public function upsertSeries(array $metadata): array;

    /**
     * Upsert a chunk of DPP observations within a transaction.
     *
     * @param  list<array<string, mixed>>  $chunk
     */
    public function upsertObservations(array $chunk): void;

    /**
     * Get the series ID map (dimension_key => DB id) for all existing series.
     *
     * @return array<string, int>
     */
    public function getSeriesIdMap(): array;

    /**
     * Get count of DPP observation records.
     */
    public function observationCount(): int;

    /**
     * Get count of countries with DPP data.
     */
    public function countryCount(): int;

    /**
     * Invalidate any cached data after import.
     */
    public function invalidateCache(): void;
}
