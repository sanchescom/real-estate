<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Clients;

use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Domain\Exceptions\BisApiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

final readonly class BisApiClient implements SdmxApiSource
{
    private const BASE_URL = 'https://stats.bis.org/api/v2/data/dataflow/BIS';

    private const CIRCUIT_KEY = 'circuit:bis-api';

    private const MAX_FAILURES = 5;

    private const DECAY_SECONDS = 60;

    private const CONNECT_TIMEOUT = 5;

    private const READ_TIMEOUT = 30;

    /** @var list<int> */
    private const RETRY_DELAYS_MS = [500, 1500, 4000];

    /** @var list<int> */
    private const RETRYABLE_STATUSES = [429, 502, 503];

    public function fetchSpp(array $params = []): string
    {
        $country = $this->extractCountry($params);

        return $this->fetch("/WS_SPP/1.0/Q.{$country}..", $params);
    }

    public function fetchDpp(array $params = []): string
    {
        $country = $this->extractCountry($params);

        return $this->fetch("/WS_DPP/1.0/Q.{$country}.....", $params);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function fetch(string $path, array $params): string
    {
        $query = ['format' => 'csv'];
        if (isset($params['lastNObservations'])) {
            $query['lastNObservations'] = $params['lastNObservations'];
        }

        return $this->request($path, $query);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function extractCountry(array $params): string
    {
        $value = $params['country'] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $path, array $query): string
    {
        $this->assertCircuitClosed();

        try {
            $response = $this->executeRequest(self::BASE_URL.$path, $query);
        } catch (RequestException $e) {
            RateLimiter::hit(self::CIRCUIT_KEY, self::DECAY_SECONDS);

            throw BisApiUnavailableException::httpError($e->response->status(), $e);
        } catch (ConnectionException $e) {
            RateLimiter::hit(self::CIRCUIT_KEY, self::DECAY_SECONDS);

            throw BisApiUnavailableException::connectionFailed($e);
        }

        if ($response->failed()) {
            RateLimiter::hit(self::CIRCUIT_KEY, self::DECAY_SECONDS);

            throw BisApiUnavailableException::httpError($response->status());
        }

        RateLimiter::clear(self::CIRCUIT_KEY);

        return $response->body();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function executeRequest(string $url, array $query): Response
    {
        return Http::connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::READ_TIMEOUT)
            ->retry(
                self::RETRY_DELAYS_MS,
                when: fn (\Throwable $e): bool => $this->isRetryable($e),
            )
            ->get($url, $query);
    }

    private function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && in_array($e->response->status(), self::RETRYABLE_STATUSES, true);
    }

    private function assertCircuitClosed(): void
    {
        if (RateLimiter::tooManyAttempts(self::CIRCUIT_KEY, self::MAX_FAILURES)) {
            throw BisApiUnavailableException::circuitOpen(
                RateLimiter::availableIn(self::CIRCUIT_KEY),
            );
        }
    }
}
