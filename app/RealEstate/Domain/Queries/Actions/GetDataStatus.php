<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Contracts\DataStatusRepository;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class GetDataStatus implements QueryAction
{
    public function __construct(
        private DataStatusRepository $repository,
    ) {}

    /**
     * @return array{
     *     countries: int,
     *     spp_records: int,
     *     spp_last_import: ?string,
     *     dpp_series: int,
     *     dpp_records: int,
     *     dpp_last_import: ?string,
     * }
     */
    public function __invoke(): array
    {
        return $this->repository->getStatus();
    }
}
