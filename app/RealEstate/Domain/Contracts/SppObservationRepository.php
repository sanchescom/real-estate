<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Contracts;

use App\RealEstate\Domain\Data\SppQuery;

interface SppObservationRepository
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function findByCountry(SppQuery $query): array;

    public function countryExists(string $countryCode): bool;

    public function countryName(string $countryCode): ?string;

    /**
     * Upsert a chunk of SPP observations within a transaction.
     *
     * @param  list<array<string, mixed>>  $chunk
     */
    public function upsertObservations(array $chunk): void;

    /**
     * Invalidate any cached data after import.
     */
    public function invalidateCache(): void;
}
