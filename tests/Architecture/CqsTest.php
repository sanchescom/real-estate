<?php

declare(strict_types=1);

use App\Shared\Domain\Contracts\CommandAction;
use App\Shared\Domain\Contracts\QueryAction;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Structural: final readonly + marker interface
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} command actions are final readonly and implement CommandAction")
        ->expect("App\\{$ctx}\\Domain\\Commands\\Actions")
        ->toBeClasses()
        ->toBeFinal()
        ->toBeReadonly()
        ->toImplement(CommandAction::class);

    arch("{$ctx} query actions are final readonly and implement QueryAction")
        ->expect("App\\{$ctx}\\Domain\\Queries\\Actions")
        ->toBeClasses()
        ->toBeFinal()
        ->toBeReadonly()
        ->toImplement(QueryAction::class);
}

/*
|--------------------------------------------------------------------------
| Return-type enforcement (CQS)
|--------------------------------------------------------------------------
*/

test('command actions return void', function (): void {
    $appRoot = dirname(__DIR__, 2).'/app/';

    foreach (boundedContexts() as $ctx) {
        $ns = "App\\{$ctx}\\Domain\\Commands\\Actions";
        $dir = $appRoot.str_replace(['App\\', '\\'], ['', '/'], $ns);
        if (! is_dir($dir)) {
            continue;
        }

        $files = glob($dir.'/*.php');
        if ($files === [] || $files === false) {
            continue;
        }

        foreach ($files as $file) {
            $class = $ns.'\\'.basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $ref = new ReflectionMethod($class, '__invoke');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull("$class::__invoke() must declare a return type");
            expect((string) $returnType)->toBe('void', "$class::__invoke() must return void, got {$returnType}");
        }
    }
});

test('query actions must not return void', function (): void {
    $appRoot = dirname(__DIR__, 2).'/app/';

    foreach (boundedContexts() as $ctx) {
        $ns = "App\\{$ctx}\\Domain\\Queries\\Actions";
        $dir = $appRoot.str_replace(['App\\', '\\'], ['', '/'], $ns);
        if (! is_dir($dir)) {
            continue;
        }

        $files = glob($dir.'/*.php');
        if ($files === [] || $files === false) {
            continue;
        }

        foreach ($files as $file) {
            $class = $ns.'\\'.basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $ref = new ReflectionMethod($class, '__invoke');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull("$class::__invoke() must declare a return type");
            expect((string) $returnType)->not->toBe('void', "$class::__invoke() must not return void");
        }
    }
});

/*
|--------------------------------------------------------------------------
| Dependency constraints
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} query actions must not dispatch events")
        ->expect("App\\{$ctx}\\Domain\\Queries\\Actions")
        ->not->toUse(Event::class);

    arch("{$ctx} query actions must not queue jobs")
        ->expect("App\\{$ctx}\\Domain\\Queries\\Actions")
        ->not->toUse(Bus::class)
        ->not->toUse(Dispatcher::class);
}

/*
|--------------------------------------------------------------------------
| Storage facade ban in domain layer
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} domain must not use Storage facade")
        ->expect("App\\{$ctx}\\Domain")
        ->not->toUse(Storage::class);
}

/*
|--------------------------------------------------------------------------
| Domain must not depend on HTTP layer
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} domain must not depend on Illuminate HTTP")
        ->expect("App\\{$ctx}\\Domain")
        ->not->toUse('Illuminate\Http');
}
