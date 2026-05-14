<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Queries\Actions;

use App\RealEstate\Domain\Queries\Contracts\DataStatusRepository;
use App\Shared\Domain\Contracts\QueryAction;

final readonly class GetDataStatus implements QueryAction
{
    public function __construct(
        private DataStatusRepository $repository,
    ) {}

    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return $this->repository->getStatus();
    }
}
