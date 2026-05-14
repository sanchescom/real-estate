<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Actions;

use App\RealEstate\Domain\Commands\ChunkFlusher;
use App\RealEstate\Domain\Commands\ChunkPipeline;
use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Domain\Commands\Contracts\SppCsvParser;
use App\RealEstate\Domain\Commands\Contracts\SppObservationStore;
use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;
use App\RealEstate\Domain\Commands\ImportReporter;
use App\RealEstate\Domain\Data\SppObservationData;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\Domain\Contracts\CommandAction;
use Psr\Log\LoggerInterface;

final readonly class FetchSppUpdates implements CommandAction
{
    public function __construct(
        private SdmxApiSource $apiSource,
        private SppCsvParser $parser,
        private SppObservationStore $store,
        private CountryStore $countryStore,
        private ChunkFlusher $flusher,
        private ImportReporter $reporter,
        private LoggerInterface $logger,
        private TempFileStorage $tempStorage,
    ) {}

    public function __invoke(?string $country = null): void
    {
        $startTime = hrtime(true);
        $csv = $this->apiSource->fetchSpp([
            'country' => $country ?? '',
            'lastNObservations' => RealEstateConstants::FETCH_LAST_N_OBSERVATIONS,
        ]);
        $tmpFile = $this->tempStorage->write('spp_fetch_', $csv);

        try {
            [$imported, $skipped, $errors, $countries] = $this->processFile($tmpFile);
        } finally {
            $this->tempStorage->delete($tmpFile);
        }

        if ($countries !== []) {
            $this->countryStore->upsertCountries($countries, 'SPP');
            $this->store->invalidateCache();
        }

        $this->logger->info('SPP fetch sanity check', ['db_observations' => $this->store->observationCount()]);
        $this->reporter->report('SPP', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'country_filter' => $country,
            'duration_ms' => (int) ((hrtime(true) - $startTime) / 1_000_000),
        ]);
    }

    /**
     * @return array{int, int, int, array<string, string>}
     */
    private function processFile(string $tmpFile): array
    {
        return $this->flusher->processChunked(new ChunkPipeline(
            items: $this->parser->parse($tmpFile),
            toRow: fn (SppObservationData $data): ?array => $data->isValid() ? $data->toUpsertRow() : null,
            toCountry: fn (SppObservationData $data): ?array => $data->isValid()
                ? [$data->countryCode, $data->countryName]
                : null,
            upsertFn: $this->store->upsertObservations(...),
        ));
    }
}
