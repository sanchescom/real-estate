<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Contracts;

use App\RealEstate\Domain\Data\PaginationQuery;

interface CountryRepository
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function list(PaginationQuery $query): array;
}
