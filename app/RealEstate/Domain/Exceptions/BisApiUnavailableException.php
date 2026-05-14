<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Exceptions;

use RuntimeException;

final class BisApiUnavailableException extends RuntimeException
{
    public static function httpError(int $status, ?\Throwable $previous = null): self
    {
        return new self("BIS API error: HTTP {$status}", previous: $previous);
    }

    public static function connectionFailed(?\Throwable $previous = null): self
    {
        return new self('BIS API connection failed', previous: $previous);
    }

    public static function circuitOpen(int $retryAfter): self
    {
        return new self("Circuit breaker open — BIS API unavailable. Retry after {$retryAfter}s.");
    }
}
