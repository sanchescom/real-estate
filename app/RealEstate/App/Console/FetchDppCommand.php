<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\FetchDppUpdates;
use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class FetchDppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:fetch-dpp
        {--country= : Filter by country code (e.g. US, DE)}';

    protected $description = 'Fetch latest DPP data from BIS SDMX API';

    public function handle(FetchDppUpdates $action, DppDataStore $store): int
    {
        $country = $this->option('country');

        $this->info('Fetching DPP updates from BIS API...');

        ($action)(country: $country);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations in DB', number_format($store->observationCount())],
                ['Countries in DB', number_format($store->countryCount())],
            ],
        );

        return self::SUCCESS;
    }
}
