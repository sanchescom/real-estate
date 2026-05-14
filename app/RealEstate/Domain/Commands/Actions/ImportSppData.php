<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Actions;

use App\RealEstate\Domain\Commands\ChunkFlusher;
use App\RealEstate\Domain\Commands\ChunkPipeline;
use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Domain\Commands\Contracts\SppCsvParser;
use App\RealEstate\Domain\Commands\Contracts\SppObservationStore;
use App\RealEstate\Domain\Commands\ImportReporter;
use App\RealEstate\Domain\Data\SppObservationData;
use App\Shared\Domain\Contracts\CommandAction;
use Psr\Log\LoggerInterface;

final readonly class ImportSppData implements CommandAction
{
    private const BULK_URL = 'https://data.bis.org/static/bulk/WS_SPP_csv_flat.zip';

    public function __construct(
        private BulkFileSource $fileSource,
        private SppCsvParser $parser,
        private SppObservationStore $store,
        private CountryStore $countryStore,
        private ChunkFlusher $flusher,
        private ImportReporter $reporter,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(bool $dryRun = false): void
    {
        $startTime = hrtime(true);
        $csvPath = $this->fileSource->download(self::BULK_URL);

        /** @var array<string, string> $countries */
        $countries = [];

        [$imported, $skipped, $errors] = $this->flusher->processChunked(new ChunkPipeline(
            items: $this->parser->parse($csvPath),
            toRow: fn (SppObservationData $data): ?array => $data->isValid() ? $data->toUpsertRow() : null,
            onValid: function (SppObservationData $data) use (&$countries): void {
                $countries[$data->countryCode] = $data->countryName;
            },
            upsertFn: $this->store->upsertObservations(...),
            dryRun: $dryRun,
        ));

        if (! $dryRun) {
            $this->countryStore->upsertCountries($countries, 'SPP');
            $this->store->invalidateCache();
            $this->logSanityCheck();
        }

        $this->reporter->reportUnlessDryRun('SPP', [
            'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors,
            'countries' => count($countries),
            'duration_ms' => (int) ((hrtime(true) - $startTime) / 1_000_000),
            'dry_run' => $dryRun,
        ], $dryRun);
    }

    private function logSanityCheck(): void
    {
        $this->logger->info('SPP import sanity check', [
            'db_countries' => $this->store->countryCount(),
            'db_observations' => $this->store->observationCount(),
        ]);
    }
}
