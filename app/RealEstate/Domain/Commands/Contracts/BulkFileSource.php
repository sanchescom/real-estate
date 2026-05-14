<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

interface BulkFileSource
{
    /**
     * Download a ZIP file from URL, extract CSV, return path to extracted file.
     */
    public function download(string $url): string;
}
