<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

interface CountryStore
{
    /**
     * Upsert countries with a dataset flag (has_spp or has_dpp).
     *
     * @param  array<string, string>  $countries  code => name
     */
    public function upsertCountries(array $countries, string $dataset): void;
}
