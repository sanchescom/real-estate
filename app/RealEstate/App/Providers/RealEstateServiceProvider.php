<?php

declare(strict_types=1);

namespace App\RealEstate\App\Providers;

use App\RealEstate\App\Console\FetchDppCommand;
use App\RealEstate\App\Console\FetchSppCommand;
use App\RealEstate\App\Console\ImportDppCommand;
use App\RealEstate\App\Console\ImportSppCommand;
use App\RealEstate\App\Console\StatusCommand;
use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Commands\Contracts\DppCsvParser as DppCsvParserContract;
use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Domain\Commands\Contracts\SppCsvParser as SppCsvParserContract;
use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;
use App\RealEstate\Domain\Contracts\CountryRepository;
use App\RealEstate\Domain\Contracts\DataStatusRepository;
use App\RealEstate\Domain\Contracts\DppRepository;
use App\RealEstate\Domain\Contracts\SppObservationRepository;
use App\RealEstate\Domain\RealEstateConstants;
use App\RealEstate\Infrastructure\Clients\BisApiClient;
use App\RealEstate\Infrastructure\Clients\BisFileClient;
use App\RealEstate\Infrastructure\Parsers\DppCsvParser;
use App\RealEstate\Infrastructure\Parsers\SppCsvParser;
use App\RealEstate\Infrastructure\Repositories\CountryDatabaseRepository;
use App\RealEstate\Infrastructure\Repositories\DataStatusDatabaseRepository;
use App\RealEstate\Infrastructure\Repositories\DppDatabaseRepository;
use App\RealEstate\Infrastructure\Repositories\SppObservationDatabaseRepository;
use App\RealEstate\Infrastructure\TempFileSystemStorage;
use App\Shared\App\Contracts\BoundedContextProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RealEstateServiceProvider extends ServiceProvider implements BoundedContextProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(BulkFileSource::class, BisFileClient::class);
        $this->app->bind(SppCsvParserContract::class, SppCsvParser::class);
        $this->app->bind(TempFileStorage::class, TempFileSystemStorage::class);
        $this->app->bind(DppCsvParserContract::class, DppCsvParser::class);
        $this->app->bind(SdmxApiSource::class, BisApiClient::class);
        $this->app->bind(CountryRepository::class, CountryDatabaseRepository::class);
        $this->app->bind(SppObservationRepository::class, SppObservationDatabaseRepository::class);
        $this->app->bind(DppRepository::class, DppDatabaseRepository::class);
        $this->app->bind(DataStatusRepository::class, DataStatusDatabaseRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadCommands();
        $this->loadSchedule();
    }

    private function loadRoutes(): void
    {
        Route::middleware(['api', 'inner-api:real-estate', 'throttle:api', 'cacheResponse'])
            ->prefix('api/v1')
            ->group(__DIR__.'/../api.php');
    }

    private function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportSppCommand::class,
                ImportDppCommand::class,
                FetchSppCommand::class,
                FetchDppCommand::class,
                StatusCommand::class,
            ]);
        }
    }

    private function loadSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('real-estate:fetch-spp')
                ->monthlyOn(RealEstateConstants::FETCH_DAY_OF_MONTH, RealEstateConstants::FETCH_SPP_TIME)
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('real-estate:fetch-dpp')
                ->monthlyOn(RealEstateConstants::FETCH_DAY_OF_MONTH, RealEstateConstants::FETCH_DPP_TIME)
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
