<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Actions;

use App\RealEstate\Domain\Commands\ChunkFlusher;
use App\RealEstate\Domain\Commands\ChunkPipeline;
use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Domain\Commands\Contracts\DppCsvParser;
use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;
use App\RealEstate\Domain\Commands\ImportReporter;
use App\RealEstate\Domain\Data\DppObservationData;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\Domain\Contracts\CommandAction;
use Psr\Log\LoggerInterface;

final readonly class FetchDppUpdates implements CommandAction
{
    public function __construct(
        private SdmxApiSource $apiSource,
        private DppCsvParser $parser,
        private DppDataStore $store,
        private CountryStore $countryStore,
        private ChunkFlusher $flusher,
        private ImportReporter $reporter,
        private LoggerInterface $logger,
        private TempFileStorage $tempStorage,
    ) {}

    public function __invoke(?string $country = null): void
    {
        $startTime = hrtime(true);
        $csv = $this->apiSource->fetchDpp([
            'country' => $country ?? '',
            'lastNObservations' => RealEstateConstants::FETCH_LAST_N_OBSERVATIONS,
        ]);
        $tmpFile = $this->tempStorage->write('dpp_fetch_', $csv);

        try {
            [$imported, $skipped, $errors, $countries] = $this->fetchAndProcess($tmpFile);
        } finally {
            $this->tempStorage->delete($tmpFile);
        }

        $this->persistCountries($countries, 'DPP');

        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        $this->logger->info('DPP fetch sanity check', ['db_observations' => $this->store->observationCount()]);

        $this->reporter->report('DPP', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'country_filter' => $country,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param  array<string, string>  $countries
     */
    private function persistCountries(array $countries, string $dataset): void
    {
        if ($countries !== []) {
            $this->countryStore->upsertCountries($countries, $dataset);
            $this->store->invalidateCache();
        }
    }

    /**
     * @return array{int, int, int, array<string, string>}
     */
    private function fetchAndProcess(string $tmpFile): array
    {
        $seriesIdMap = $this->store->getSeriesIdMap();

        if ($seriesIdMap === []) {
            $this->logger->warning('DPP fetch: no series in DB. Run import-dpp first.');
        }

        return $this->flusher->processChunked(new ChunkPipeline(
            items: $this->parser->parse($tmpFile),
            toRow: fn (DppObservationData $data): ?array => $this->buildObservationRow($data, $seriesIdMap),
            toCountry: fn (DppObservationData $data): ?array => $data->countryCode !== ''
                ? [$data->countryCode, $data->countryName]
                : null,
            upsertFn: $this->store->upsertObservations(...),
        ));
    }

    /**
     * @param  array<string, int>  $seriesIdMap
     * @return array<string, mixed>|null
     */
    private function buildObservationRow(DppObservationData $data, array $seriesIdMap): ?array
    {
        if ($data->countryCode === '' || $data->period === '' || ! is_numeric($data->value)) {
            return null;
        }

        $seriesId = $seriesIdMap[$data->dimensionKey] ?? null;
        if ($seriesId === null) {
            return null;
        }

        return [
            'series_id' => $seriesId,
            'frequency' => $data->frequency,
            'period' => $data->period,
            'value' => $data->value,
            'obs_status' => $data->obsStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
