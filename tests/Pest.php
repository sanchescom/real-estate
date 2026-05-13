<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

beforeEach(function (): void {
    Http::preventStrayRequests();
});

/** @return list<string> */
function boundedContexts(): array
{
    static $contexts = null;

    if ($contexts !== null) {
        return $contexts;
    }

    $contexts = [];
    $dirs = glob(dirname(__DIR__).'/app/*/Domain', GLOB_ONLYDIR);

    foreach ($dirs ?: [] as $dir) {
        $name = basename(dirname($dir));
        if ($name !== 'Shared') {
            $contexts[] = $name;
        }
    }

    sort($contexts);

    return $contexts;
}

function captureStreamedResponse(StreamedResponse $response): string
{
    ob_start();
    $response->sendContent();

    return ob_get_clean();
}
