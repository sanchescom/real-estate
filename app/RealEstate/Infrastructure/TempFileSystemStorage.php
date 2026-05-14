<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure;

use App\RealEstate\Domain\Commands\Contracts\TempFileStorage;

final readonly class TempFileSystemStorage implements TempFileStorage
{
    public function write(string $prefix, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($path, $content);

        return $path;
    }

    public function delete(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
