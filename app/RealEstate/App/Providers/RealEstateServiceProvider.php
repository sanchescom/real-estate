<?php

declare(strict_types=1);

namespace App\RealEstate\App\Providers;

use App\Shared\App\Contracts\BoundedContextProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RealEstateServiceProvider extends ServiceProvider implements BoundedContextProvider
{
    #[\Override]
    public function register(): void
    {
        // Bindings will be added as features are implemented
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        Route::middleware(['api', 'inner-api:real-estate', 'throttle:api'])
            ->prefix('api/v1')
            ->group(__DIR__.'/../api.php');
    }
}
