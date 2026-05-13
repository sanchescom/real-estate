<?php

declare(strict_types=1);

namespace App\RealEstate\Domain;

final readonly class RealEstateConstants
{
    // Pagination
    public const DEFAULT_PAGE_LIMIT = 50;

    public const MAX_PAGE_LIMIT = 500;

    // Import
    public const CHUNK_SIZE = 1000;

    public const SERIES_CHUNK_SIZE = 100;

    // Cache
    public const CACHE_TTL_SECONDS = 86400;

    public const CACHE_CONTROL_HEADER = 'public, max-age=86400';

    // Source
    public const SOURCE_NAME = 'BIS';

    public const SPP_BASE_YEAR = '2010 = 100';

    // Schedule
    public const FETCH_DAY_OF_MONTH = 25;

    public const FETCH_SPP_TIME = '03:00';

    public const FETCH_DPP_TIME = '04:00';

    // Error codes
    public const ERROR_COUNTRY_NOT_FOUND = 'COUNTRY_NOT_FOUND';
}
