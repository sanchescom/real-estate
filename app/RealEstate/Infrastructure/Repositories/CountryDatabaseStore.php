<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Infrastructure\Models\Country;

final readonly class CountryDatabaseStore implements CountryStore
{
    /**
     * @param  array<string, string>  $countries
     */
    #[\Override]
    public function upsertCountries(array $countries, string $dataset): void
    {
        $flag = $dataset === 'SPP' ? 'has_spp' : 'has_dpp';

        $rows = [];
        foreach ($countries as $code => $name) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                $flag => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Country::upsert($rows, ['code'], ['name', $flag, 'updated_at']);
    }
}
