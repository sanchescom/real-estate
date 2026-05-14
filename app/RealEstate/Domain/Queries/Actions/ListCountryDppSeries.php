<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Contracts\DppRepository;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class ListCountryDppSeries implements QueryAction
{
    public function __construct(
        private DppRepository $repository,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public function __invoke(string $countryCode): ?array
    {
        if (! $this->repository->countryExists($countryCode)) {
            return null;
        }

        $series = $this->repository->seriesForCountry($countryCode);

        return [
            'data' => $series,
            'meta' => [
                'country_code' => $countryCode,
                'total' => count($series),
            ],
        ];
    }
}
