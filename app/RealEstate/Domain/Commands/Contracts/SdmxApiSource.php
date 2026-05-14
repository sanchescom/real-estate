<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Commands\Contracts;

interface SdmxApiSource
{
    /**
     * Fetch SPP data from SDMX API as raw CSV string.
     *
     * @param  array<string, mixed>  $params
     */
    public function fetchSpp(array $params = []): string;

    /**
     * Fetch DPP data from SDMX API as raw CSV string.
     *
     * @param  array<string, mixed>  $params
     */
    public function fetchDpp(array $params = []): string;
}
