<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\FetchSppUpdates;
use App\RealEstate\Domain\Contracts\DataStatusRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class FetchSppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:fetch-spp
        {--country= : Filter by country code (e.g. US, DE)}';

    protected $description = 'Fetch latest SPP data from BIS SDMX API';

    public function handle(FetchSppUpdates $action, DataStatusRepository $statusRepo): int
    {
        $country = $this->option('country');

        $this->info('Fetching SPP updates from BIS API...');

        ($action)(country: $country);

        $status = $statusRepo->getStatus();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations in DB', number_format($status['spp_records'])],
                ['Countries in DB', number_format($status['countries'])],
            ],
        );

        return self::SUCCESS;
    }
}
