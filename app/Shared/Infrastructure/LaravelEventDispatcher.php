<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\Contracts\DomainEvent;
use App\Shared\Domain\Contracts\EventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {}

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
