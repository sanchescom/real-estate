<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands;

use Closure;
use Generator;

/**
 * Configuration for a chunked import pipeline.
 *
 * @template T
 */
final readonly class ChunkPipeline
{
    /**
     * @param  Generator<int, T>  $items
     * @param  Closure(T): ?array<string, mixed>  $toRow  Returns null to skip the item.
     * @param  Closure(T): ?array{string, string}  $toCountry  Returns [code, name] or null.
     * @param  callable(list<array<string, mixed>>): void  $upsertFn
     */
    public function __construct(
        public Generator $items,
        public Closure $toRow,
        public Closure $toCountry,
        public mixed $upsertFn,
        public bool $dryRun = false,
    ) {}
}
