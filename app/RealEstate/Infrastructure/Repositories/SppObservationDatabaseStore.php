<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Commands\Contracts\SppObservationStore;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\SppObservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;

final readonly class SppObservationDatabaseStore implements SppObservationStore
{
    #[\Override]
    public function upsertObservations(array $chunk): void
    {
        $now = now();
        $stamped = array_map(
            fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
            $chunk,
        );

        DB::transaction(function () use ($stamped): void {
            SppObservation::upsert(
                $stamped,
                ['country_code', 'value_type', 'unit_measure', 'period'],
                ['value', 'obs_status', 'updated_at'],
            );
        });
    }

    #[\Override]
    public function observationCount(): int
    {
        return SppObservation::count();
    }

    #[\Override]
    public function countryCount(): int
    {
        return Country::where('has_spp', true)->count();
    }

    #[\Override]
    public function invalidateCache(): void
    {
        Cache::tags(['real-estate'])->flush();
        ResponseCache::clear();
    }
}
