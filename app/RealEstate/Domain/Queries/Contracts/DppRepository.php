<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Contracts;

use App\RealEstate\Domain\Data\DppQuery;

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
}
