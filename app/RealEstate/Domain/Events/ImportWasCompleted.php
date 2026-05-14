<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Events;

use App\Shared\Domain\Contracts\DomainEvent;
use Carbon\CarbonImmutable;

final readonly class ImportWasCompleted implements DomainEvent
{
    public function __construct(
        public string $dataset,
        public int $imported,
        public int $skipped,
        public int $errors,
        public int $durationMs,
        public CarbonImmutable $completedAt,
    ) {}
}
