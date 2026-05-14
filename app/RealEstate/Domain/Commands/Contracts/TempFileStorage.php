<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

interface TempFileStorage
{
    /**
     * Write content to a temp file and return the path.
     */
    public function write(string $prefix, string $content): string;

    /**
     * Delete a temp file.
     */
    public function delete(string $path): void;
}
