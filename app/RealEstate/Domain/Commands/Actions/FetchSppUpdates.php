<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Actions;

use App\RealEstate\Domain\Commands\ChunkFlusher;
use App\RealEstate\Domain\Commands\ChunkPipeline;
use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Domain\Commands\Contracts\SppCsvParser;
use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;
use App\RealEstate\Domain\Commands\ImportReporter;
use App\RealEstate\Domain\Contracts\CountryRepository;
use App\RealEstate\Domain\Contracts\SppObservationRepository;
use App\RealEstate\Domain\Data\SppObservationData;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\Domain\Contracts\CommandAction;

final readonly class FetchSppUpdates implements CommandAction
{
    public function __construct(
        private SdmxApiSource $apiSource,
        private SppCsvParser $parser,
        private SppObservationRepository $store,
        private CountryRepository $countryStore,
        private ChunkFlusher $flusher,
        private ImportReporter $reporter,
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

        $this->reporter->report('SPP', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'country_filter' => $country,
            'duration_ms' => (int) ((hrtime(true) - $startTime) / 1_000_000),
        ], new \DateTimeImmutable);
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
