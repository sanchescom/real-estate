<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Exceptions;

use RuntimeException;

final class BisFileDownloadException extends RuntimeException
{
    public static function httpFailed(string $url, int $status): self
    {
        return new self("Failed to download {$url}: HTTP {$status}");
    }

    public static function zipOpenFailed(string $path): self
    {
        return new self("Failed to open ZIP: {$path}");
    }

    public static function noCsvFound(string $url): self
    {
        return new self("No CSV files found in ZIP from {$url}");
    }

    public static function circuitOpen(int $retryAfter): self
    {
        return new self("Circuit breaker open — BIS file server unavailable. Retry after {$retryAfter}s.");
    }
}
