<?php

declare(strict_types=1);

use App\RealEstate\Infrastructure\Parsers\BisCodeParser;
use App\RealEstate\Infrastructure\Parsers\SppCsvParser;
use Psr\Log\NullLogger;

it('parses code from CODE:Label format', function (): void {
    expect(BisCodeParser::parseCode('US: United States'))->toBe('US');
    expect(BisCodeParser::parseCode('N: Nominal'))->toBe('N');
    expect(BisCodeParser::parseCode('628: Index, 2010 = 100'))->toBe('628');
    expect(BisCodeParser::parseCode('Q: Quarterly'))->toBe('Q');
});

it('parses label from CODE:Label format', function (): void {
    expect(BisCodeParser::parseLabel('US: United States'))->toBe('United States');
    expect(BisCodeParser::parseLabel('N: Nominal'))->toBe('Nominal');
});

it('handles values without colon separator', function (): void {
    expect(BisCodeParser::parseCode('US'))->toBe('US');
    expect(BisCodeParser::parseLabel('US'))->toBe('US');
});

it('parses SPP bulk CSV file correctly', function (): void {
    $csv = <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","VALUE:Value","UNIT_MEASURE:Unit of measure","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","UNIT_MULT:Unit Multiplier","BREAKS:Breaks","COVERAGE:Coverage","TITLE_TS:Title (tseries level)","OBS_STATUS:Observation Status","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation"
dataflow,"BIS:WS_SPP(1.0): Selected residential property prices",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",2020-Q1,208.2134,"0: Units",,,,A: Normal value,"F: Free",
dataflow,"BIS:WS_SPP(1.0): Selected residential property prices",I,"Q: Quarterly","DE: Germany","R: Real","771: Year-on-year changes, in per cent",2020-Q2,5.1234,"0: Units",,,,A: Normal value,"F: Free",
CSV;

    $tmpFile = tempnam(sys_get_temp_dir(), 'spp_test_');
    file_put_contents($tmpFile, $csv);

    $parser = new SppCsvParser(new NullLogger);
    $results = iterator_to_array($parser->parse($tmpFile));

    unlink($tmpFile);

    expect($results)->toHaveCount(2);

    expect($results[0]->countryCode)->toBe('US');
    expect($results[0]->countryName)->toBe('United States');
    expect($results[0]->valueType)->toBe('N');
    expect($results[0]->unitMeasure)->toBe('628');
    expect($results[0]->period)->toBe('2020-Q1');
    expect($results[0]->value)->toBe('208.2134');
    expect($results[0]->obsStatus)->toBe('A');

    expect($results[1]->countryCode)->toBe('DE');
    expect($results[1]->valueType)->toBe('R');
    expect($results[1]->unitMeasure)->toBe('771');
});

it('parses SPP SDMX API CSV file correctly', function (): void {
    $csv = <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,FREQ,REF_AREA,VALUE,UNIT_MEASURE,TIME_PERIOD,OBS_VALUE,UNIT_MULT,BREAKS,COVERAGE,TITLE_TS,OBS_STATUS,OBS_CONF,OBS_PRE_BREAK
dataflow,"BIS:WS_SPP(1.0)",I,Q,US,N,628,2020-Q1,208.2134,0,,,,A,F,
dataflow,"BIS:WS_SPP(1.0)",I,Q,DE,R,771,2020-Q2,5.1234,0,,,,A,F,
CSV;

    $tmpFile = tempnam(sys_get_temp_dir(), 'spp_test_');
    file_put_contents($tmpFile, $csv);

    $parser = new SppCsvParser(new NullLogger);
    $results = iterator_to_array($parser->parse($tmpFile));

    unlink($tmpFile);

    expect($results)->toHaveCount(2);

    expect($results[0]->countryCode)->toBe('US');
    expect($results[0]->countryName)->toBe('US');
    expect($results[0]->valueType)->toBe('N');
    expect($results[0]->unitMeasure)->toBe('628');
    expect($results[0]->period)->toBe('2020-Q1');
    expect($results[0]->value)->toBe('208.2134');
    expect($results[0]->obsStatus)->toBe('A');

    expect($results[1]->countryCode)->toBe('DE');
    expect($results[1]->valueType)->toBe('R');
    expect($results[1]->unitMeasure)->toBe('771');
});

it('skips rows without period or value', function (): void {
    $csv = <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","VALUE:Value","UNIT_MEASURE:Unit of measure","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","UNIT_MULT:Unit Multiplier","BREAKS:Breaks","COVERAGE:Coverage","TITLE_TS:Title (tseries level)","OBS_STATUS:Observation Status","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation"
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",,,"0: Units",,,,,,
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",2020-Q1,100.0,"0: Units",,,,A: Normal value,"F: Free",
CSV;

    $tmpFile = tempnam(sys_get_temp_dir(), 'spp_test_');
    file_put_contents($tmpFile, $csv);

    $parser = new SppCsvParser(new NullLogger);
    $results = iterator_to_array($parser->parse($tmpFile));

    unlink($tmpFile);

    expect($results)->toHaveCount(1);
    expect($results[0]->period)->toBe('2020-Q1');
});
