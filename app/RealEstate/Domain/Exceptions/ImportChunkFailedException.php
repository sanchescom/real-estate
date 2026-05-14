<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Exceptions;

use RuntimeException;

final class ImportChunkFailedException extends RuntimeException
{
    public static function forDataset(string $dataset, int $chunkSize, \Throwable $previous): self
    {
        return new self(
            "{$dataset} import chunk failed ({$chunkSize} rows): {$previous->getMessage()}",
            previous: $previous,
        );
    }
}
