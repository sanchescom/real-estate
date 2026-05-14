<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\FetchDppUpdates;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\DppObservation;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class FetchDppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:fetch-dpp
        {--country= : Filter by country code (e.g. US, DE)}';

    protected $description = 'Fetch latest DPP data from BIS SDMX API';

    public function handle(FetchDppUpdates $action): int
    {
        $country = $this->option('country');

        $this->info('Fetching DPP updates from BIS API...');

        ($action)(country: $country);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations in DB', number_format(DppObservation::count())],
                ['Countries in DB', number_format(Country::where('has_dpp', true)->count())],
            ],
        );

        return self::SUCCESS;
    }
}
