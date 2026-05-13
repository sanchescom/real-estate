<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Domain classes ban technical suffixes
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} domain bans technical suffixes")
        ->expect("App\\{$ctx}\\Domain")
        ->not->toHaveSuffix('Manager')
        ->not->toHaveSuffix('Handler')
        ->not->toHaveSuffix('Processor')
        ->not->toHaveSuffix('Helper')
        ->not->toHaveSuffix('Util');
}

/*
|--------------------------------------------------------------------------
| Domain events use past-tense names (ban present-tense suffixes)
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} events ban present-tense suffixes")
        ->expect("App\\{$ctx}\\Domain\\Events")
        ->not->toHaveSuffix('Changed')
        ->not->toHaveSuffix('Updated')
        ->not->toHaveSuffix('Saved')
        ->not->toHaveSuffix('Modified');
}

/*
|--------------------------------------------------------------------------
| Domain classes must be final
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} domain classes are final")
        ->expect("App\\{$ctx}\\Domain")
        ->classes()
        ->toBeFinal();
}

/*
|--------------------------------------------------------------------------
| Value Objects must be final readonly
|--------------------------------------------------------------------------
*/

foreach (boundedContexts() as $ctx) {
    arch("{$ctx} value objects are readonly")
        ->expect("App\\{$ctx}\\Domain\\ValueObjects")
        ->toBeReadonly();
}

/*
|--------------------------------------------------------------------------
| Cross-context isolation (bounded contexts communicate via events only)
|--------------------------------------------------------------------------
*/

$allContexts = boundedContexts();

foreach ($allContexts as $ctx) {
    $others = array_filter($allContexts, fn (string $other): bool => $other !== $ctx);

    foreach ($others as $other) {
        arch("{$ctx} domain must not reference {$other} context")
            ->expect("App\\{$ctx}\\Domain")
            ->not->toUse("App\\{$other}");
    }
}

/*
|--------------------------------------------------------------------------
| Cross-context App-layer isolation
|--------------------------------------------------------------------------
*/

foreach ($allContexts as $ctx) {
    $others = array_filter($allContexts, fn (string $other): bool => $other !== $ctx);

    foreach ($others as $other) {
        arch("{$ctx} app must not reference {$other} app layer")
            ->expect("App\\{$ctx}\\App")
            ->not->toUse("App\\{$other}\\App");

        arch("{$ctx} app must not reference {$other} infrastructure")
            ->expect("App\\{$ctx}\\App")
            ->not->toUse("App\\{$other}\\Infrastructure");

        arch("{$ctx} infrastructure must not reference {$other}")
            ->expect("App\\{$ctx}\\Infrastructure")
            ->not->toUse("App\\{$other}");
    }
}
