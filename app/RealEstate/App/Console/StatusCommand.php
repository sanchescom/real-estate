<?php

declare(strict_types=1);

namespace App\RealEstate\App\Console;

use App\RealEstate\Domain\Queries\Actions\GetDataStatus;
use App\RealEstate\Domain\RealEstateConstants;
use Illuminate\Console\Command;

final class StatusCommand extends Command
{
    protected $signature = 'real-estate:status';

    protected $description = 'Show current status of BIS Real Estate data';

    public function handle(GetDataStatus $action): int
    {
        $status = ($action)();

        $this->newLine();
        $this->info('BIS Real Estate Status');
        $this->line('──────────────────────');

        $this->table(['Metric', 'Value'], [
            ['Countries', number_format($status['countries'])],
            ['SPP records', number_format($status['spp_records'])],
            ['SPP last import', $status['spp_last_import'] ?? 'never'],
            ['DPP series', number_format($status['dpp_series'])],
            ['DPP records', number_format($status['dpp_records'])],
            ['DPP last import', $status['dpp_last_import'] ?? 'never'],
            ['Next fetch', RealEstateConstants::FETCH_DAY_OF_MONTH.'th of month at '
                .RealEstateConstants::FETCH_SPP_TIME.'/'.RealEstateConstants::FETCH_DPP_TIME],
        ]);

        return self::SUCCESS;
    }
}
