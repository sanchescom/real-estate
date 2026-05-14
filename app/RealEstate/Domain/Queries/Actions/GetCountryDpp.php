<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Contracts\DppRepository;
use App\RealEstate\Domain\Data\DppQuery;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class GetCountryDpp implements QueryAction
{
    public function __construct(
        private DppRepository $repository,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public function __invoke(DppQuery $query): ?array
    {
        if (! $this->repository->countryExists($query->countryCode)) {
            return null;
        }

        $result = $this->repository->findByCountry($query);

        return [
            'data' => $result['data'],
            'meta' => [
                'country_code' => $query->countryCode,
                'source' => RealEstateConstants::SOURCE_NAME,
                'dataset' => 'DPP',
                'total' => $result['total'],
                'offset' => $query->offset,
                'limit' => $query->limit,
                'filters' => $query->filters,
            ],
        ];
    }
}
