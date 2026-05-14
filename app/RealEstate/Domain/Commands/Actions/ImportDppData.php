<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Actions;

use App\RealEstate\Domain\Commands\ChunkFlusher;
use App\RealEstate\Domain\Commands\ChunkPipeline;
use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Domain\Commands\Contracts\DppCsvParser;
use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use App\RealEstate\Domain\Commands\ImportReporter;
use App\RealEstate\Domain\Data\DppObservationData;
use App\Shared\Domain\Contracts\CommandAction;
use Psr\Log\LoggerInterface;

final readonly class ImportDppData implements CommandAction
{
    private const BULK_URL = 'https://data.bis.org/static/bulk/WS_DPP_csv_flat.zip';

    public function __construct(
        private BulkFileSource $fileSource,
        private DppCsvParser $parser,
        private DppDataStore $store,
        private CountryStore $countryStore,
        private ChunkFlusher $flusher,
        private ImportReporter $reporter,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(bool $dryRun = false): void
    {
        $startTime = hrtime(true);
        $csvPath = $this->fileSource->download(self::BULK_URL);
        $seriesIdMap = $this->prepareSeries($csvPath, $dryRun);

        /** @var array<string, string> $countries */
        $countries = [];

        [$imported, $skipped, $errors] = $this->flusher->processChunked(new ChunkPipeline(
            items: $this->parser->parse($csvPath),
            toRow: fn (DppObservationData $data): ?array => $this->buildRow($data, $seriesIdMap, $dryRun),
            onValid: function (DppObservationData $data) use (&$countries): void {
                $countries[$data->countryCode] = $data->countryName;
            },
            upsertFn: $this->store->upsertObservations(...),
            dryRun: $dryRun,
        ));

        if (! $dryRun) {
            $this->countryStore->upsertCountries($countries, 'DPP');
            $this->store->invalidateCache();
            $this->logSanityCheck();
        }

        $this->reporter->reportUnlessDryRun('DPP', [
            'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors,
            'countries' => count($countries),
            'duration_ms' => (int) ((hrtime(true) - $startTime) / 1_000_000),
            'dry_run' => $dryRun,
        ], $dryRun);
    }

    /**
     * @return array<string, int>
     */
    private function prepareSeries(string $csvPath, bool $dryRun): array
    {
        $metadata = $this->parser->parseMetadata($csvPath);
        $this->logger->info('DPP metadata parsed', ['series_count' => count($metadata)]);

        return $dryRun ? [] : $this->store->upsertSeries($metadata);
    }

    /**
     * @param  array<string, int>  $seriesIdMap
     * @return array<string, mixed>|null
     */
    private function buildRow(DppObservationData $data, array $seriesIdMap, bool $dryRun): ?array
    {
        if (! $data->isValid()) {
            return null;
        }

        $seriesId = $seriesIdMap[$data->dimensionKey] ?? null;
        if ($seriesId === null && ! $dryRun) {
            return null;
        }

        return [
            'series_id' => $seriesId ?? 0,
            'frequency' => $data->frequency,
            'period' => $data->period,
            'value' => $data->value,
            'obs_status' => $data->obsStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function logSanityCheck(): void
    {
        $this->logger->info('DPP import sanity check', [
            'db_countries' => $this->store->countryCount(),
            'db_observations' => $this->store->observationCount(),
        ]);
    }
}
