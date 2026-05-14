<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\FetchSppUpdates;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\SppObservation;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class FetchSppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:fetch-spp
        {--country= : Filter by country code (e.g. US, DE)}';

    protected $description = 'Fetch latest SPP data from BIS SDMX API';

    public function handle(FetchSppUpdates $action): int
    {
        $country = $this->option('country');

        $this->info('Fetching SPP updates from BIS API...');

        ($action)(country: $country);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations in DB', number_format(SppObservation::count())],
                ['Countries in DB', number_format(Country::where('has_spp', true)->count())],
            ],
        );

        return self::SUCCESS;
    }
}
