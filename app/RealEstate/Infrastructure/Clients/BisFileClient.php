<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Clients;

use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Exceptions\BisFileDownloadException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final readonly class BisFileClient implements BulkFileSource
{
    private const int CONNECT_TIMEOUT = 5;

    private const int READ_TIMEOUT = 60;

    private const int RETRY_COUNT = 3;

    private const int RETRY_DELAY_MS = 1000;

    private const string CIRCUIT_KEY = 'circuit:bis-file';

    private const int MAX_FAILURES = 3;

    private const int DECAY_SECONDS = 120;

    public function download(string $url): string
    {
        $this->assertCircuitClosed();

        $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::READ_TIMEOUT)
            ->retry(self::RETRY_COUNT, self::RETRY_DELAY_MS)
            ->get($url);

        if ($response->failed()) {
            RateLimiter::hit(self::CIRCUIT_KEY, self::DECAY_SECONDS);

            throw BisFileDownloadException::httpFailed($url, $response->status());
        }

        RateLimiter::clear(self::CIRCUIT_KEY);

        return $this->saveAndExtract($url, $response->body());
    }

    private function saveAndExtract(string $url, string $body): string
    {
        $zipFilename = 'bis_download_'.md5($url).'.zip';
        Storage::put($zipFilename, $body);

        return $this->extractCsv(Storage::path($zipFilename), $zipFilename, $url);
    }

    private function extractCsv(string $zipPath, string $zipFilename, string $url): string
    {
        $extractDir = 'bis_extract_'.md5($url);
        $extractPath = Storage::path($extractDir);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw BisFileDownloadException::zipOpenFailed($zipPath);
        }

        $zip->extractTo($extractPath);
        $zip->close();
        Storage::delete($zipFilename);

        return $this->resolveExtractedCsv($extractDir, $extractPath, $url);
    }

    private function resolveExtractedCsv(string $extractDir, string $extractPath, string $url): string
    {
        $csvFiles = array_filter(
            Storage::files($extractDir),
            fn (string $file): bool => str_ends_with($file, '.csv'),
        );

        if ($csvFiles === []) {
            throw BisFileDownloadException::noCsvFound($url);
        }

        $resolved = Storage::path(array_values($csvFiles)[0]);
        $this->assertNoZipSlip($resolved, $extractPath);

        return $resolved;
    }

    private function assertCircuitClosed(): void
    {
        if (RateLimiter::tooManyAttempts(self::CIRCUIT_KEY, self::MAX_FAILURES)) {
            throw BisFileDownloadException::circuitOpen(
                RateLimiter::availableIn(self::CIRCUIT_KEY),
            );
        }
    }

    private function assertNoZipSlip(string $filePath, string $extractPath): void
    {
        $realDir = realpath($extractPath);
        $realFile = realpath($filePath);

        if ($realDir === false || $realFile === false || ! str_starts_with($realFile, $realDir)) {
            throw new BisFileDownloadException('Zip-slip: file escapes target directory.');
        }
    }
}
