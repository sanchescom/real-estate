<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

use App\RealEstate\Domain\RealEstateConstants;

final readonly class DppQuery
{
    /**
     * @param  array<string, string>  $filters  Keys: area, property_type, vintage, freq, from, to
     */
    public function __construct(
        public string $countryCode,
        public array $filters = [],
        public ?string $sort = null,
        public int $offset = 0,
        public int $limit = RealEstateConstants::DEFAULT_PAGE_LIMIT,
    ) {}

    /** @return array<string, mixed> */
    public function linkParams(): array
    {
        $params = array_filter(['sort' => $this->sort]);

        foreach ($this->filters as $key => $value) {
            $params["filter[{$key}]"] = $value;
        }

        return $params;
    }
}
