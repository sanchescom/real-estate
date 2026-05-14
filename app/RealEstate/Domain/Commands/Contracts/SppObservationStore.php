<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

interface SppObservationStore
{
    /**
     * Upsert a chunk of SPP observations within a transaction.
     *
     * @param  list<array<string, mixed>>  $chunk
     */
    public function upsertObservations(array $chunk): void;

    /**
     * Get count of SPP observation records.
     */
    public function observationCount(): int;

    /**
     * Get count of countries with SPP data.
     */
    public function countryCount(): int;

    /**
     * Invalidate any cached data after import.
     */
    public function invalidateCache(): void;
}
