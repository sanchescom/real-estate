<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Contracts;

use App\RealEstate\Domain\Data\PaginationQuery;

interface CountryRepository
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function list(PaginationQuery $query): array;

    /**
     * Upsert countries with a dataset flag (has_spp or has_dpp).
     *
     * @param  array<string, string>  $countries  code => name
     */
    public function upsertCountries(array $countries, string $dataset): void;
}
