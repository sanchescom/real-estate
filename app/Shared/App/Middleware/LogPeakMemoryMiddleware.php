<?php

declare(strict_types=1);

namespace App\Shared\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogPeakMemoryMiddleware
{
    /** Log when peak memory exceeds this percentage of memory_limit. */
    private const WARNING_THRESHOLD = 0.8;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request): void
    {
        $peakBytes = memory_get_peak_usage(true);
        $limitBytes = $this->getMemoryLimitBytes();

        if ($limitBytes > 0 && ($peakBytes / $limitBytes) >= self::WARNING_THRESHOLD) {
            Log::warning('High memory usage', [
                'peak_mb' => round($peakBytes / 1024 / 1024, 1),
                'limit_mb' => round($limitBytes / 1024 / 1024, 1),
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
            ]);
        }
    }

    private function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return 0;
        }

        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
