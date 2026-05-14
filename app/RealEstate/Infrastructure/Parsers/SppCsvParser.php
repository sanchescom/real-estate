<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Parsers;

use App\RealEstate\Domain\Commands\Contracts\SppCsvParser as SppCsvParserContract;
use App\RealEstate\Domain\Data\SppObservationData;
use Generator;

final readonly class SppCsvParser implements SppCsvParserContract
{
    use ReadsBisCsv;

    private const COLUMN_MAPPING = [
        'REF_AREA' => 'Reference area',
        'VALUE' => 'Value',
        'UNIT_MEASURE' => 'Unit of measure',
        'TIME_PERIOD' => 'Time period or range',
        'OBS_VALUE' => 'Observation Value',
        'OBS_STATUS' => 'Observation Status',
    ];

    /**
     * Bulk CSV headers use "KEY:Label" format, SDMX API headers use plain "KEY".
     * We detect which format by checking if the first header contains a colon.
     */
    public function parse(string $filePath): Generator
    {
        $records = $this->readObservationRecords($filePath, self::COLUMN_MAPPING);

        foreach ($records as $entry) {
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
     * @param  array<string, string>  $record
     * @param  array<string, string>  $cols
     */
    private function buildObservation(array $record, array $cols, bool $isBulkFormat): SppObservationData
    {
        $countryRaw = $record[$cols['REF_AREA']] ?? '';
        $obsStatusRaw = trim($record[$cols['OBS_STATUS']] ?? '');

        return new SppObservationData(
            countryCode: BisCodeParser::parseCode($countryRaw),
            countryName: $isBulkFormat ? BisCodeParser::parseLabel($countryRaw) : BisCodeParser::parseCode($countryRaw),
            valueType: BisCodeParser::parseCode($record[$cols['VALUE']] ?? ''),
            unitMeasure: BisCodeParser::parseCode($record[$cols['UNIT_MEASURE']] ?? ''),
            period: trim($record[$cols['TIME_PERIOD']] ?? ''),
            value: trim($record[$cols['OBS_VALUE']] ?? ''),
            obsStatus: $obsStatusRaw !== '' ? BisCodeParser::parseCode($obsStatusRaw) : null,
        );
    }
}
