<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Parsers;

final readonly class BisCodeParser
{
    /**
     * Parse a "CODE: Label" format string, return the code part.
     * If no colon is found (API format), returns the trimmed raw value.
     */
    public static function parseCode(string $raw): string
    {
        $pos = strpos($raw, ':');
        if ($pos === false) {
            return trim($raw);
        }

        return trim(substr($raw, 0, $pos));
    }

    /**
     * Parse a "CODE: Label" format string, return the label part.
     * If no colon is found (API format), returns the trimmed raw value.
     */
    public static function parseLabel(string $raw): string
    {
        $pos = strpos($raw, ':');
        if ($pos === false) {
            return trim($raw);
        }

        return trim(substr($raw, $pos + 1));
    }
}
