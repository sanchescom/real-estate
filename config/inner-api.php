<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Inner API Realms
    |--------------------------------------------------------------------------
    |
    | Each realm defines a set of valid API keys for a specific group of
    | endpoints. Middleware usage: inner-api:{realm}
    |
    | Multiple comma-separated keys per realm allow zero-downtime rotation.
    |
    */

    'realms' => [
        'real-estate' => [
            'keys' => array_filter(explode(',', (string) env('INNER_API_KEYS_REAL_ESTATE', ''))),
        ],
    ],

];
