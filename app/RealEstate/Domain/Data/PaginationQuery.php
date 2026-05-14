<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

use App\RealEstate\Domain\RealEstateConstants;

final readonly class PaginationQuery
{
    public function __construct(
        public int $offset = 0,
        public int $limit = RealEstateConstants::DEFAULT_PAGE_LIMIT,
        public ?string $sort = null,
    ) {}

    /**
     * Extract offset and limit from a validated page array.
     *
     * @param  array{offset?: int, limit?: int}  $page
     * @return array{int, int}
     */
    public static function extractPage(array $page): array
    {
        return [
            (int) ($page['offset'] ?? 0),
            (int) ($page['limit'] ?? RealEstateConstants::DEFAULT_PAGE_LIMIT),
        ];
    }
}
