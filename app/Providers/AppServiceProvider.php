<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\App\Contracts\BoundedContextProvider;
use App\Shared\App\HealthChecks\OpcacheCheck;
use App\Shared\Domain\Contracts\EventDispatcher;
use App\Shared\Infrastructure\LaravelEventDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Symfony\Component\Finder\Finder;

final class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);

        $this->registerBoundedContextProviders();
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
        DateFactory::use(CarbonImmutable::class);

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(100)->by(
            $request->header('X-API-Key') ?: $request->ip(),
        ));

        Gate::define('viewApiDocs', fn (): bool => app()->isLocal());

        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            CacheCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            OpcacheCheck::new()
                ->failWhenMemoryUsageAbovePercentage(80)
                ->failWhenHitRateBelowPercentage(99),
        ]);
    }

    private function registerBoundedContextProviders(): void
    {
        $dirs = Finder::create()
            ->in(app_path())
            ->depth(0)
            ->directories()
            ->exclude('Shared');

        foreach ($dirs as $dir) {
            $context = $dir->getFilename();
            $class = "App\\{$context}\\App\\Providers\\{$context}ServiceProvider";

            if (class_exists($class) && is_subclass_of($class, BoundedContextProvider::class)) {
                $this->app->register($class);
            }
        }
    }
}
