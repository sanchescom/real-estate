<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\ImportDppData;
use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class ImportDppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:import-dpp
        {--dry-run : Parse and validate without writing to database}';

    protected $description = 'Import DPP (Detailed Property Prices) data from BIS bulk CSV';

    public function handle(ImportDppData $action, DppDataStore $store): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no data will be written to database');
        }

        $this->info('Downloading and parsing DPP bulk data...');

        ($action)(dryRun: $dryRun);

        $this->newLine();
        $this->info('DPP Import Report:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations', number_format($store->observationCount())],
                ['Countries', number_format($store->countryCount())],
                ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
            ],
        );

        return self::SUCCESS;
    }
}
