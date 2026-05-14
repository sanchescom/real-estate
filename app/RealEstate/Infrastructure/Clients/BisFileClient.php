<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Clients;

use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Domain\Exceptions\BisFileDownloadException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final readonly class BisFileClient implements BulkFileSource
{
    public function download(string $url): string
    {
        $response = Http::connectTimeout(5)
            ->timeout(60)
            ->retry(3, 1000)
            ->get($url);

        if ($response->failed()) {
            throw BisFileDownloadException::httpFailed($url, $response->status());
        }

        $zipFilename = 'bis_download_'.md5($url).'.zip';
        Storage::put($zipFilename, $response->body());

        $zipPath = Storage::path($zipFilename);

        return $this->extractCsv($zipPath, $zipFilename, $url);
    }

    private function extractCsv(string $zipPath, string $zipFilename, string $url): string
    {
        $extractDir = 'bis_extract_'.md5($url);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw BisFileDownloadException::zipOpenFailed($zipPath);
        }

        $zip->extractTo(Storage::path($extractDir));
        $zip->close();

        Storage::delete($zipFilename);

        $csvFiles = array_filter(
            Storage::files($extractDir),
            fn (string $file): bool => str_ends_with($file, '.csv'),
        );

        if ($csvFiles === []) {
            throw BisFileDownloadException::noCsvFound($url);
        }

        return Storage::path(array_values($csvFiles)[0]);
    }
}
