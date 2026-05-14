<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Contracts\CountryRepository;
use App\RealEstate\Domain\Data\PaginationQuery;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class ListCountries implements QueryAction
{
    public function __construct(
        private CountryRepository $repository,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function __invoke(PaginationQuery $query): array
    {
        $result = $this->repository->list($query);

        return [
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'offset' => $query->offset,
                'limit' => $query->limit,
            ],
        ];
    }
}
