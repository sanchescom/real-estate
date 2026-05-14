<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Queries\Contracts\DataStatusRepository;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;
use App\RealEstate\Infrastructure\Models\SppObservation;

final readonly class DataStatusDatabaseRepository implements DataStatusRepository
{
    public function getStatus(): array
    {
        $sppLast = SppObservation::latest('updated_at')->first();
        $dppLast = DppObservation::latest('updated_at')->first();

        return [
            'countries' => Country::count(),
            'spp_records' => SppObservation::count(),
            'spp_last_import' => $sppLast?->updated_at?->format('Y-m-d H:i'),
            'dpp_series' => DppSeries::count(),
            'dpp_records' => DppObservation::count(),
            'dpp_last_import' => $dppLast?->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
