<?php

declare(strict_types=1);

namespace App\Shared\App\HealthChecks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

final class OpcacheCheck extends Check
{
    protected float $failWhenMemoryUsageAbovePercentage = 80;

    protected float $failWhenHitRateBelowPercentage = 99;

    public function failWhenMemoryUsageAbovePercentage(float $percentage): self
    {
        $this->failWhenMemoryUsageAbovePercentage = $percentage;

        return $this;
    }

    public function failWhenHitRateBelowPercentage(float $percentage): self
    {
        $this->failWhenHitRateBelowPercentage = $percentage;

        return $this;
    }

    public function run(): Result
    {
        if (! function_exists('opcache_get_status')) {
            return Result::make()->failed('OPcache extension is not loaded.');
        }

        /** @var array{opcache_enabled: bool, memory_usage: array{used_memory: int, free_memory: int, wasted_memory: int, current_wasted_percentage: float}, opcache_statistics: array{hits: int, misses: int, opcache_hit_rate: float, oom_restarts: int, num_cached_scripts: int, num_cached_keys: int, max_cached_keys: int}}|false $status */
        $status = opcache_get_status(false);

        if ($status === false) {
            return Result::make()->failed('OPcache is not enabled.');
        }

        $metrics = $this->extractMetrics($status);
        $result = Result::make()
            ->shortSummary("Memory: {$metrics['memory_usage_percent']}%, Hit rate: {$metrics['hit_rate']}%")
            ->meta($metrics);

        return $this->evaluateThresholds($result, $metrics);
    }

    /**
     * @param  array{memory_usage: array{used_memory: int, free_memory: int, wasted_memory: int, current_wasted_percentage: float}, opcache_statistics: array{hits: int, misses: int, opcache_hit_rate: float, oom_restarts: int, num_cached_scripts: int, num_cached_keys: int, max_cached_keys: int}}  $status
     * @return array{memory_usage_percent: float, hit_rate: float, oom_restarts: int, cached_scripts: int, cached_keys: int, max_cached_keys: int, wasted_percentage: float}
     */
    private function extractMetrics(array $status): array
    {
        $memory = $status['memory_usage'];
        $stats = $status['opcache_statistics'];
        $totalMemory = $memory['used_memory'] + $memory['free_memory'] + $memory['wasted_memory'];

        return [
            'memory_usage_percent' => round(($memory['used_memory'] / $totalMemory) * 100, 1),
            'hit_rate' => round($stats['opcache_hit_rate'], 2),
            'oom_restarts' => $stats['oom_restarts'],
            'cached_scripts' => $stats['num_cached_scripts'],
            'cached_keys' => $stats['num_cached_keys'],
            'max_cached_keys' => $stats['max_cached_keys'],
            'wasted_percentage' => $memory['current_wasted_percentage'],
        ];
    }

    /**
     * @param  array{memory_usage_percent: float, hit_rate: float, oom_restarts: int}  $metrics
     */
    private function evaluateThresholds(Result $result, array $metrics): Result
    {
        if ($metrics['oom_restarts'] > 0) {
            return $result->failed("OPcache OOM restarts detected: {$metrics['oom_restarts']}.");
        }

        if ($metrics['memory_usage_percent'] >= $this->failWhenMemoryUsageAbovePercentage) {
            return $result->failed("OPcache memory usage is {$metrics['memory_usage_percent']}% (threshold: {$this->failWhenMemoryUsageAbovePercentage}%).");
        }

        if ($metrics['hit_rate'] < $this->failWhenHitRateBelowPercentage) {
            return $result->failed("OPcache hit rate is {$metrics['hit_rate']}% (threshold: {$this->failWhenHitRateBelowPercentage}%).");
        }

        return $result->ok();
    }
}
