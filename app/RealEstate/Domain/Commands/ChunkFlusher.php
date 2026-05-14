<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands;

use App\RealEstate\Domain\Exceptions\ImportChunkFailedException;
use App\RealEstate\Domain\RealEstateConstants;
use Psr\Log\LoggerInterface;

final readonly class ChunkFlusher
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Flush a chunk via the given upsert callable, catching failures.
     *
     * @param  callable(list<array<string, mixed>>): void  $upsertFn
     * @param  list<array<string, mixed>>  $chunk
     * @return int Number of failed rows (0 on success, chunk size on failure)
     */
    public function flush(callable $upsertFn, array $chunk, bool $dryRun = false): int
    {
        if ($dryRun) {
            return 0;
        }

        try {
            $upsertFn($chunk);
        } catch (ImportChunkFailedException $e) {
            $this->logger->error('Import chunk failed', [
                'error' => $e->getMessage(),
                'chunk_size' => count($chunk),
            ]);

            return count($chunk);
        }

        return 0;
    }

    /**
     * Iterate a generator of items, accumulate valid rows into chunks, and flush each chunk.
     *
     * @template T
     *
     * @param  ChunkPipeline<T>  $pipeline
     * @return array{int, int, int} [imported, skipped, errors]
     */
    public function processChunked(ChunkPipeline $pipeline): array
    {
        $imported = $skipped = $errors = 0;
        /** @var list<array<string, mixed>> $chunk */
        $chunk = [];

        foreach ($pipeline->items as $item) {
            $row = ($pipeline->toRow)($item);
            if ($row === null) {
                $skipped++;
                continue;
            }

            ($pipeline->onValid)($item);
            $chunk[] = $row;

            if (count($chunk) >= RealEstateConstants::CHUNK_SIZE) {
                $errors += $this->flush($pipeline->upsertFn, $chunk, $pipeline->dryRun);
                $imported += count($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $errors += $this->flush($pipeline->upsertFn, $chunk, $pipeline->dryRun);
            $imported += count($chunk);
        }

        return [$imported - $errors, $skipped, $errors];
    }
}
