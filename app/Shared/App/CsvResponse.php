<?php

declare(strict_types=1);

namespace App\Shared\App;

use League\Csv\Bom;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class CsvResponse
{
    /** @var list<string> */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  iterable<int, array<string, mixed>|object>  $rows
     * @param  list<string>|null  $headers
     */
    public function create(iterable $rows, string $filename, ?array $headers = null): StreamedResponse
    {
        $rowArrays = $this->materializeRows($rows);
        $safeFilename = preg_replace('/[^\w\-. ]/', '_', $filename);

        return new StreamedResponse(
            function () use ($rowArrays, $headers): void {
                $this->writeCsvToOutput($rowArrays, $headers);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$safeFilename}\"",
                'Cache-Control' => 'no-store',
            ],
        );
    }

    /**
     * @param  iterable<int, array<string, mixed>|object>  $rows
     * @return list<array<string, mixed>>
     */
    private function materializeRows(iterable $rows): array
    {
        /** @var list<array<string, mixed>> $result */
        $result = [];

        foreach ($rows as $row) {
            /** @var array<string, mixed> $converted */
            $converted = is_object($row) && method_exists($row, 'toArray')
                ? $row->toArray()
                : (array) $row;
            $result[] = $converted;
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $rowArrays
     * @param  list<string>|null  $headers
     */
    private function writeCsvToOutput(array $rowArrays, ?array $headers): void
    {
        $handle = fopen('php://output', 'wb');
        if ($handle === false) {
            return;
        }

        if ($headers !== null || $rowArrays !== []) {
            fwrite($handle, Bom::Utf8->value);
        }

        $csv = Writer::from($handle);

        if ($headers !== null) {
            $csv->insertOne($headers);
        } elseif ($rowArrays !== []) {
            $csv->insertOne(array_keys($rowArrays[0]));
        }

        foreach ($rowArrays as $row) {
            $csv->insertOne(array_map(
                fn (mixed $value): mixed => is_string($value)
                    ? $this->sanitize($value)
                    : $value,
                array_values($row),
            ));
        }
    }

    private function sanitize(string $value): string
    {
        foreach (self::DANGEROUS_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'".$value;
            }
        }

        return $value;
    }
}
