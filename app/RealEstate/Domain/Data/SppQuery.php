<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

use App\RealEstate\Domain\RealEstateConstants;

final readonly class SppQuery
{
    /**
     * @param  array<string, string>  $filters  Keys: type, metric, from, to
     */
    public function __construct(
        public string $countryCode,
        public array $filters = [],
        public ?string $sort = null,
        public int $offset = 0,
        public int $limit = RealEstateConstants::DEFAULT_PAGE_LIMIT,
    ) {}
}
