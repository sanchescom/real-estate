<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Contracts;

use App\RealEstate\Domain\Data\DppQuery;
use App\RealEstate\Domain\Data\DppSeriesData;

interface DppRepository
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function findByCountry(DppQuery $query): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function seriesForCountry(string $countryCode): array;

    public function countryExists(string $countryCode): bool;

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
     * Invalidate any cached data after import.
     */
    public function invalidateCache(): void;
}
