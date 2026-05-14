<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Parsers;

use App\RealEstate\Domain\Commands\Contracts\DppCsvParser as DppCsvParserContract;
use App\RealEstate\Domain\Data\DppObservationData;
use App\RealEstate\Domain\Data\DppSeriesData;
use Generator;

final readonly class DppCsvParser implements DppCsvParserContract
{
    use ReadsBisCsv;

    private const COLUMN_MAPPING = [
        'FREQ' => 'Frequency',
        'REF_AREA' => 'Reference area',
        'COVERED_AREA' => 'Covered area',
        'RE_TYPE' => 'Real estate type',
        'RE_VINTAGE' => 'Real estate vintage',
        'COMPILING_ORG' => 'Compiling agency',
        'PRICED_UNIT' => 'Priced unit',
        'ADJUST_CODED' => 'Seasonal adjustment',
        'UNIT_MEASURE' => 'Unit of measure',
        'TIME_PERIOD' => 'Time period or range',
        'OBS_VALUE' => 'Observation Value',
        'OBS_STATUS' => 'Observation Status',
        'TITLE_GRP' => 'Title',
        'COVERAGE' => 'Coverage',
        'DATA_COMP' => 'Data compilation',
    ];

    /**
     * @return Generator<int, DppObservationData>
     */
    public function parse(string $filePath): Generator
    {
        foreach ($this->readObservationRecords($filePath, self::COLUMN_MAPPING) as $entry) {
            /** @var array<string, string> $row */
            $row = $entry['row'];
            /** @var array<string, string> $cols */
            $cols = $entry['cols'];
            /** @var bool $isBulk */
            $isBulk = $entry['isBulk'];

            yield $this->buildObservation($row, $cols, $isBulk);
        }
    }

    /**
     * @return array<string, DppSeriesData> keyed by dimension key
     */
    public function parseMetadata(string $filePath): array
    {
        $metadata = [];

        $records = $this->readRecords($filePath, self::COLUMN_MAPPING);

        foreach ($records as $entry) {
            /** @var array<string, string> $row */
            $row = $entry['row'];
            /** @var array<string, string> $cols */
            $cols = $entry['cols'];
            /** @var bool $isBulk */
            $isBulk = $entry['isBulk'];

            $period = trim($row[$cols['TIME_PERIOD']] ?? '');
            $obsValue = trim($row[$cols['OBS_VALUE']] ?? '');

            if ($period !== '' || $obsValue !== '') {
                continue;
            }

            $series = $this->buildSeriesData($row, $cols, $isBulk);
            $metadata[$series->dimensionKey()] = $series;
        }

        return $metadata;
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<string, string>  $cols
     */
    private function buildObservation(array $record, array $cols, bool $isBulkFormat): DppObservationData
    {
        $countryRaw = $record[$cols['REF_AREA']] ?? '';

        return new DppObservationData(
            dimensionKey: $this->buildDimensionKey($record, $cols),
            countryCode: BisCodeParser::parseCode($countryRaw),
            countryName: $isBulkFormat ? BisCodeParser::parseLabel($countryRaw) : BisCodeParser::parseCode($countryRaw),
            frequency: BisCodeParser::parseCode($record[$cols['FREQ']] ?? ''),
            period: trim($record[$cols['TIME_PERIOD']] ?? ''),
            value: trim($record[$cols['OBS_VALUE']] ?? ''),
            obsStatus: $this->parseOptionalCode($record[$cols['OBS_STATUS']] ?? ''),
        );
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<string, string>  $cols
     */
    private function buildSeriesData(array $record, array $cols, bool $isBulkFormat): DppSeriesData
    {
        $countryRaw = $record[$cols['REF_AREA']] ?? '';

        return new DppSeriesData(
            countryCode: BisCodeParser::parseCode($countryRaw),
            countryName: $isBulkFormat ? BisCodeParser::parseLabel($countryRaw) : BisCodeParser::parseCode($countryRaw),
            coveredArea: BisCodeParser::parseCode($record[$cols['COVERED_AREA']] ?? ''),
            propertyType: BisCodeParser::parseCode($record[$cols['RE_TYPE']] ?? ''),
            vintage: BisCodeParser::parseCode($record[$cols['RE_VINTAGE']] ?? ''),
            compilingOrg: BisCodeParser::parseCode($record[$cols['COMPILING_ORG']] ?? ''),
            pricedUnit: BisCodeParser::parseCode($record[$cols['PRICED_UNIT']] ?? ''),
            seasonalAdj: BisCodeParser::parseCode($record[$cols['ADJUST_CODED']] ?? ''),
            unitMeasure: trim($record[$cols['UNIT_MEASURE']] ?? ''),
            title: $this->trimOrNull($record[$cols['TITLE_GRP']] ?? ''),
            coverage: $this->trimOrNull($record[$cols['COVERAGE']] ?? ''),
            dataCompilation: $this->trimOrNull($record[$cols['DATA_COMP']] ?? ''),
        );
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<string, string>  $cols
     */
    private function buildDimensionKey(array $record, array $cols): string
    {
        return implode('|', [
            BisCodeParser::parseCode($record[$cols['REF_AREA']] ?? ''),
            BisCodeParser::parseCode($record[$cols['COVERED_AREA']] ?? ''),
            BisCodeParser::parseCode($record[$cols['RE_TYPE']] ?? ''),
            BisCodeParser::parseCode($record[$cols['RE_VINTAGE']] ?? ''),
            BisCodeParser::parseCode($record[$cols['COMPILING_ORG']] ?? ''),
            BisCodeParser::parseCode($record[$cols['PRICED_UNIT']] ?? ''),
            BisCodeParser::parseCode($record[$cols['ADJUST_CODED']] ?? ''),
        ]);
    }

    private function parseOptionalCode(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        return BisCodeParser::parseCode($trimmed);
    }

    private function trimOrNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
