<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Commands\Actions\ImportSppData;
use App\RealEstate\Domain\Contracts\DataStatusRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class ImportSppCommand extends Command implements Isolatable
{
    protected $signature = 'real-estate:import-spp
        {--dry-run : Parse and validate without writing to database}';

    protected $description = 'Import SPP (Selected Property Prices) data from BIS bulk CSV';

    public function handle(ImportSppData $action, DataStatusRepository $statusRepo): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no data will be written to database');
        }

        $this->info('Downloading and parsing SPP bulk data...');

        ($action)(dryRun: $dryRun);

        $status = $statusRepo->getStatus();

        $this->newLine();
        $this->info('SPP Import Report:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Observations', number_format($status['spp_records'])],
                ['Countries', number_format($status['countries'])],
                ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
            ],
        );

        return self::SUCCESS;
    }
}
