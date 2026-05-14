<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Data\SppQuery;
use App\RealEstate\Domain\Queries\Contracts\SppRepository;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class GetCountrySpp implements QueryAction
{
    public function __construct(
        private SppRepository $repository,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public function __invoke(SppQuery $query): ?array
    {
        if (! $this->repository->countryExists($query->countryCode)) {
            return null;
        }

        $result = $this->repository->findByCountry($query);

        return [
            'data' => $result['data'],
            'meta' => [
                'country_code' => $query->countryCode,
                'country_name' => $this->repository->countryName($query->countryCode),
                'type' => $query->filters['type'] ?? null,
                'metric' => $query->filters['metric'] ?? null,
                'base_year' => RealEstateConstants::SPP_BASE_YEAR,
                'frequency' => 'quarterly',
                'source' => RealEstateConstants::SOURCE_NAME,
                'total' => $result['total'],
                'from' => $query->filters['from'] ?? null,
                'to' => $query->filters['to'] ?? null,
                'offset' => $query->offset,
                'limit' => $query->limit,
            ],
        ];
    }
}
