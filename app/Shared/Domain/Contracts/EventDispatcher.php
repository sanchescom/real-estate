<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface EventDispatcher
{
    public function dispatch(DomainEvent ...$events): void;
}
