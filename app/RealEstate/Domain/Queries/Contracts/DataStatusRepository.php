<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Contracts;

interface DataStatusRepository
{
    /**
     * @return array{countries: int, spp_records: int, spp_last_import: ?string, dpp_series: int, dpp_records: int, dpp_last_import: ?string}
     */
    public function getStatus(): array;
}
