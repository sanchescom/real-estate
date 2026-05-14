<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\ValueObjects;

final readonly class ImportResult
{
    public function __construct(
        public int $imported,
        public int $skipped,
        public int $errors,
        public int $countries,
        public int $durationMs,
    ) {}
}
