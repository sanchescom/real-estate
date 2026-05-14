<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Contracts;

interface DataStatusRepository
{
    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array;
}
