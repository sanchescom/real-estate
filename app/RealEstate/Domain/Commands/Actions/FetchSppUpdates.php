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
        $csv = $this->apiSource->fetchSpp(['country' => $country ?? '', 'lastNObservations' => 5]);
        $tmpFile = $this->tempStorage->write('spp_fetch_', $csv);

        /** @var array<string, string> $countries */
        $countries = [];

        try {
            [$imported, $skipped, $errors] = $this->processFile($tmpFile, $countries);
        } finally {
            $this->tempStorage->delete($tmpFile);
        }

        if ($countries !== []) {
            $this->countryStore->upsertCountries($countries, 'SPP');
            $this->store->invalidateCache();
        }

        $this->logger->info('SPP fetch sanity check', ['db_observations' => $this->store->observationCount()]);
        $this->reporter->report('SPP', [
            'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors,
            'country_filter' => $country,
            'duration_ms' => (int) ((hrtime(true) - $startTime) / 1_000_000),
        ]);
    }

    /**
     * @param  array<string, string>  $countries  Populated by reference.
     * @return array{int, int, int}
     */
    private function processFile(string $tmpFile, array &$countries): array
    {
        return $this->flusher->processChunked(new ChunkPipeline(
            items: $this->parser->parse($tmpFile),
            toRow: fn (SppObservationData $data): ?array => $data->isValid() ? $data->toUpsertRow() : null,
            onValid: function (SppObservationData $data) use (&$countries): void {
                $countries[$data->countryCode] = $data->countryName;
            },
            upsertFn: $this->store->upsertObservations(...),
        ));
    }
}
