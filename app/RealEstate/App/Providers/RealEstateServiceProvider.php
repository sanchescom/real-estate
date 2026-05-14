<?php

declare(strict_types=1);

namespace App\RealEstate\App\Providers;

use App\RealEstate\App\Console\ImportDppCommand;
use App\RealEstate\App\Console\ImportSppCommand;
use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Commands\Contracts\CountryStore;
use App\RealEstate\Domain\Commands\Contracts\DppCsvParser as DppCsvParserContract;
use App\RealEstate\Domain\Commands\Contracts\DppDataStore;
use App\RealEstate\Domain\Commands\Contracts\SppCsvParser as SppCsvParserContract;
use App\RealEstate\Domain\Commands\Contracts\SppObservationStore;
use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;
use App\RealEstate\Infrastructure\Clients\BisFileClient;
use App\RealEstate\Infrastructure\Parsers\DppCsvParser;
use App\RealEstate\Infrastructure\Parsers\SppCsvParser;
use App\RealEstate\Infrastructure\Repositories\CountryDatabaseStore;
use App\RealEstate\Infrastructure\Repositories\DppDataDatabaseStore;
use App\RealEstate\Infrastructure\Repositories\SppObservationDatabaseStore;
use App\RealEstate\Infrastructure\TempFileSystemStorage;
use App\Shared\App\Contracts\BoundedContextProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RealEstateServiceProvider extends ServiceProvider implements BoundedContextProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(BulkFileSource::class, BisFileClient::class);
        $this->app->bind(SppCsvParserContract::class, SppCsvParser::class);
        $this->app->bind(SppObservationStore::class, SppObservationDatabaseStore::class);
        $this->app->bind(CountryStore::class, CountryDatabaseStore::class);
        $this->app->bind(TempFileStorage::class, TempFileSystemStorage::class);
        $this->app->bind(DppCsvParserContract::class, DppCsvParser::class);
        $this->app->bind(DppDataStore::class, DppDataDatabaseStore::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadCommands();
    }

    private function loadRoutes(): void
    {
        Route::middleware(['api', 'inner-api:real-estate', 'throttle:api'])
            ->prefix('api/v1')
            ->group(__DIR__.'/../api.php');
    }

    private function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportSppCommand::class,
                ImportDppCommand::class,
            ]);
        }
    }
}
