<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands;

use App\RealEstate\Domain\Events\ImportWasCompleted;
use App\Shared\Domain\Contracts\EventDispatcher;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final readonly class ImportReporter
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $stats
     */
    public function logCompletion(string $dataset, array $stats): void
    {
        $this->logger->info("{$dataset} import completed", $stats);
    }

    /**
     * Log completion and dispatch an event.
     *
     * @param  array{imported: int, skipped: int, errors: int, duration_ms: int, country_filter?: ?string}  $stats
     */
    public function report(string $dataset, array $stats): void
    {
        $this->logCompletion($dataset, $stats);
        $this->dispatchEvent($dataset, $stats);
    }

    /**
     * Log completion and optionally dispatch an event (skipped during dry-run).
     *
     * @param  array{imported: int, skipped: int, errors: int, countries: int, duration_ms: int, dry_run?: bool}  $stats
     */
    public function reportUnlessDryRun(string $dataset, array $stats, bool $dryRun): void
    {
        $this->logCompletion($dataset, $stats);

        if (! $dryRun) {
            $this->dispatchEvent($dataset, $stats);
        }
    }

    /**
     * @param  array{imported: int, skipped: int, errors: int, duration_ms: int}  $stats
     */
    public function dispatchEvent(string $dataset, array $stats): void
    {
        $this->events->dispatch(new ImportWasCompleted(
            dataset: $dataset,
            imported: $stats['imported'],
            skipped: $stats['skipped'],
            errors: $stats['errors'],
            durationMs: $stats['duration_ms'],
            completedAt: CarbonImmutable::now(),
        ));
    }
}
