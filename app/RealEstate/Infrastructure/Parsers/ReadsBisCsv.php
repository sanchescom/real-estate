<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Parsers;

use Generator;
use League\Csv\Reader;

trait ReadsBisCsv
{
    /**
     * Open a CSV file and yield each record with resolved columns and format info.
     *
     * @param  array<string, string>  $columnMapping  key => label mapping for resolving columns
     * @return Generator<int, array{row: array<string, string>, cols: array<string, string>, isBulk: bool}>
     */
    private function readRecords(string $filePath, array $columnMapping): Generator
    {
        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);

        /** @var list<string> $headers */
        $headers = array_values($reader->getHeader());
        $isBulk = $this->detectBulkFormat($headers);
        $cols = $this->resolveColumnNames($headers, $columnMapping);

        foreach ($reader->getRecords() as $record) {
            /** @var array<string, string> $row */
            $row = array_map(fn (mixed $v): string => is_string($v) ? $v : '', $record);

            yield ['row' => $row, 'cols' => $cols, 'isBulk' => $isBulk];
        }
    }

    /**
     * Filter readRecords() output to only yield rows with non-empty period and obs_value.
     *
     * @param  array<string, string>  $columnMapping
     * @return Generator<int, array{row: array<string, string>, cols: array<string, string>, isBulk: bool}>
     */
    private function readObservationRecords(string $filePath, array $columnMapping): Generator
    {
        foreach ($this->readRecords($filePath, $columnMapping) as $entry) {
            $period = trim($entry['row'][$entry['cols']['TIME_PERIOD']] ?? '');
            $obsValue = trim($entry['row'][$entry['cols']['OBS_VALUE']] ?? '');

            if ($period === '' || $obsValue === '') {
                continue;
            }

            yield $entry;
        }
    }

    /**
     * Detect bulk format by checking if any header contains a colon.
     *
     * @param  list<string>  $headers
     */
    private function detectBulkFormat(array $headers): bool
    {
        foreach ($headers as $header) {
            if (str_contains($header, ':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve column names for both bulk ("KEY:Label") and API ("KEY") formats.
     *
     * @param  list<string>  $headers
     * @param  array<string, string>  $mapping
     * @return array<string, string>
     */
    private function resolveColumnNames(array $headers, array $mapping): array
    {
        $resolved = [];
        foreach ($mapping as $key => $label) {
            $bulkName = "{$key}:{$label}";
            $resolved[$key] = in_array($bulkName, $headers, true) ? $bulkName : $key;
        }

        return $resolved;
    }
}
