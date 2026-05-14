<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use App\RealEstate\Domain\Data\DppSeriesData;
use App\RealEstate\Domain\RealEstateConstants;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;

final readonly class DppDataDatabaseStore implements DppDataStore
{
    #[\Override]
    public function upsertSeries(array $metadata): array
    {
        $rows = $this->buildSeriesRows($metadata);

        foreach (array_chunk($rows, RealEstateConstants::SERIES_CHUNK_SIZE) as $chunk) {
            DppSeries::upsert(
                $chunk,
                [
                    'country_code', 'covered_area', 'property_type',
                    'vintage', 'compiling_org', 'priced_unit', 'seasonal_adj',
                ],
                ['unit_measure', 'title', 'coverage', 'data_compilation', 'updated_at'],
            );
        }

        return $this->getSeriesIdMap();
    }

    /**
     * @param  array<string, DppSeriesData>  $metadata
     * @return list<array<string, mixed>>
     */
    private function buildSeriesRows(array $metadata): array
    {
        $rows = [];
        foreach ($metadata as $series) {
            $rows[] = [
                'country_code' => $series->countryCode,
                'covered_area' => $series->coveredArea,
                'property_type' => $series->propertyType,
                'vintage' => $series->vintage,
                'compiling_org' => $series->compilingOrg,
                'priced_unit' => $series->pricedUnit,
                'seasonal_adj' => $series->seasonalAdj,
                'unit_measure' => $series->unitMeasure,
                'title' => $series->title,
                'coverage' => $series->coverage,
                'data_compilation' => $series->dataCompilation,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    #[\Override]
    public function upsertObservations(array $chunk): void
    {
        $now = now();
        $stamped = array_map(
            fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
            $chunk,
        );

        DB::transaction(function () use ($stamped): void {
            DppObservation::upsert(
                $stamped,
                ['series_id', 'frequency', 'period'],
                ['value', 'obs_status', 'updated_at'],
            );
        });
    }

    #[\Override]
    public function getSeriesIdMap(): array
    {
        return DppSeries::query()
            ->select([
                'id', 'country_code', 'covered_area', 'property_type',
                'vintage', 'compiling_org', 'priced_unit', 'seasonal_adj',
            ])
            ->get()
            ->mapWithKeys(fn (DppSeries $series): array => [
                implode('|', [
                    $series->country_code,
                    $series->covered_area,
                    $series->property_type,
                    $series->vintage,
                    $series->compiling_org,
                    $series->priced_unit,
                    $series->seasonal_adj,
                ]) => $series->id,
            ])
            ->all();
    }

    #[\Override]
    public function observationCount(): int
    {
        return DppObservation::count();
    }

    #[\Override]
    public function countryCount(): int
    {
        return Country::where('has_dpp', true)->count();
    }

    #[\Override]
    public function invalidateCache(): void
    {
        Cache::tags(['real-estate'])->flush();
        ResponseCache::clear();
    }
}
