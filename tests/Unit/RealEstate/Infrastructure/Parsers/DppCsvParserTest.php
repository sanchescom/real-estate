<?php

declare(strict_types=1);

use App\RealEstate\Infrastructure\Parsers\DppCsvParser;
use Psr\Log\NullLogger;

function createDppTestCsvFile(string $content): string
{
    $tmpFile = tempnam(sys_get_temp_dir(), 'dpp_test_');
    file_put_contents($tmpFile, $content);

    return $tmpFile;
}

function dppHeader(): string
{
    return 'STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","COVERED_AREA:Covered area","RE_TYPE:Real estate type","RE_VINTAGE:Real estate vintage","COMPILING_ORG:Compiling agency","PRICED_UNIT:Priced unit","ADJUST_CODED:Seasonal adjustment","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","COLLECTION:Collection Indicator","TIME_FORMAT:Time Format","AVAILABILITY:Availability","BREAKS:Breaks","COLLECTION_DETAIL:Collection explanation detail","COVERAGE:Coverage","DATA_COMP:Data compilation","DECIMALS:Decimals","DOC_METHOD:Documentation on methodology","MEASURE_DETAIL:Unit of measure detail","META_UPDATE:Documentation date","PUBLICATIONS:Dissemination format - publications","TITLE_GRP:Title","TITLE_GRP_COMPL:Title complement","TITLE_GRP_NAT:National language title","UNIT_MEASURE:Unit of measure","UNIT_MULT:Unit Multiplier","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation","OBS_STATUS:Observation Status"';
}

it('parses DPP data rows correctly', function (): void {
    $csv = dppHeader()."\n".
        'dataflow,"BIS:WS_DPP(1.0)",I,"Q: Quarterly","US: United States","0: Whole country","1: All types of dwellings","0: All","0: Central bank","0: Per dwelling","0: Non seasonally adjusted",2020-Q1,150.1234,,,,,,,,,,,,,,,,,,,'."\n";

    $file = createDppTestCsvFile($csv);
    $parser = new DppCsvParser(new NullLogger);
    $results = iterator_to_array($parser->parse($file));
    unlink($file);

    expect($results)->toHaveCount(1);
    expect($results[0]->countryCode)->toBe('US');
    expect($results[0]->frequency)->toBe('Q');
    expect($results[0]->period)->toBe('2020-Q1');
    expect($results[0]->value)->toBe('150.1234');
});

it('parses metadata rows with unit_measure and title', function (): void {
    $csv = dppHeader()."\n".
        'dataflow,"BIS:WS_DPP(1.0)",I,,"AU: Australia","2: Capital city","3: Single-family houses - detached","0: All","2: Private sector","6: Pure price","0: Non seasonally adjusted",,,,,"A: All users",,,"Covers established houses in Sydney.",,,"4: Four",,,,"Australia - Residential property price index, all detached houses, Sydney",,,"779: Index, 2009 December = 100","0: Units",,,'."\n";

    $file = createDppTestCsvFile($csv);
    $parser = new DppCsvParser(new NullLogger);
    $metadata = $parser->parseMetadata($file);
    unlink($file);

    expect($metadata)->toHaveCount(1);

    $key = 'AU|2|3|0|2|6|0';
    expect($metadata)->toHaveKey($key);

    $series = $metadata[$key];
    expect($series->countryCode)->toBe('AU');
    expect($series->coveredArea)->toBe('2');
    expect($series->propertyType)->toBe('3');
    expect($series->unitMeasure)->toBe('779: Index, 2009 December = 100');
    expect($series->title)->toContain('Australia');
    expect($series->coverage)->toContain('Sydney');
});

it('handles all 4 TIME_PERIOD formats', function (string $period): void {
    $csv = dppHeader()."\n".
        "dataflow,\"BIS:WS_DPP(1.0)\",I,\"Q: Quarterly\",\"US: United States\",\"0: Whole country\",\"1: All\",\"0: All\",\"0: Central bank\",\"0: Per dwelling\",\"0: Non seasonally adjusted\",{$period},100.0,,,,,,,,,,,,,,,,,,,\n";

    $file = createDppTestCsvFile($csv);
    $parser = new DppCsvParser(new NullLogger);
    $results = iterator_to_array($parser->parse($file));
    unlink($file);

    expect($results)->toHaveCount(1);
    expect($results[0]->period)->toBe($period);
})->with([
    'quarterly' => '2020-Q1',
    'monthly' => '2020-01',
    'annual' => '2020',
    'half-yearly' => '2020-S1',
]);
