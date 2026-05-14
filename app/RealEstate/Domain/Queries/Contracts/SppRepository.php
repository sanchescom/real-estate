<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Contracts;

use App\RealEstate\Domain\Data\SppQuery;

interface SppRepository
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function findByCountry(SppQuery $query): array;

    public function countryExists(string $countryCode): bool;
}
