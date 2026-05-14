<?php

declare(strict_types=1);

namespace App\RealEstate\Domain;

final readonly class RealEstateConstants
{
    // Pagination

    public const int DEFAULT_PAGE_LIMIT = 50;

    public const int MAX_PAGE_LIMIT = 500;

    // Import

    public const int CHUNK_SIZE = 1000;

    public const int SERIES_CHUNK_SIZE = 100;

    // Cache

    public const int CACHE_TTL_SECONDS = 86400;

    public const string CACHE_CONTROL_HEADER = 'public, max-age=86400';

    // Source

    public const string SOURCE_NAME = 'BIS';

    public const string SPP_BASE_YEAR = '2010 = 100';

    // Schedule

    public const int FETCH_DAY_OF_MONTH = 25;

    public const string FETCH_SPP_TIME = '03:00';

    public const string FETCH_DPP_TIME = '04:00';

    // Fetch

    public const int FETCH_LAST_N_OBSERVATIONS = 5;

    // Error codes

    public const string ERROR_COUNTRY_NOT_FOUND = 'COUNTRY_NOT_FOUND';
}
