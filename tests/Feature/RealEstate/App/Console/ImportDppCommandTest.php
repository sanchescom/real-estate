<?php

declare(strict_types=1);

use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;

function createDppTestCsv(): string
{
    $header = 'STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","COVERED_AREA:Covered area","RE_TYPE:Real estate type","RE_VINTAGE:Real estate vintage","COMPILING_ORG:Compiling agency","PRICED_UNIT:Priced unit","ADJUST_CODED:Seasonal adjustment","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","COLLECTION:Collection Indicator","TIME_FORMAT:Time Format","AVAILABILITY:Availability","BREAKS:Breaks","COLLECTION_DETAIL:Collection explanation detail","COVERAGE:Coverage","DATA_COMP:Data compilation","DECIMALS:Decimals","DOC_METHOD:Documentation on methodology","MEASURE_DETAIL:Unit of measure detail","META_UPDATE:Documentation date","PUBLICATIONS:Dissemination format - publications","TITLE_GRP:Title","TITLE_GRP_COMPL:Title complement","TITLE_GRP_NAT:National language title","UNIT_MEASURE:Unit of measure","UNIT_MULT:Unit Multiplier","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation","OBS_STATUS:Observation Status"';

    // Metadata row: 33 columns, no TIME_PERIOD(11), no OBS_VALUE(12), UNIT_MEASURE at col 28
    // Cols: 0-STRUCTURE, 1-STRUCTURE_ID, 2-ACTION, 3-FREQ, 4-REF_AREA, 5-COVERED_AREA, 6-RE_TYPE, 7-RE_VINTAGE, 8-COMPILING_ORG, 9-PRICED_UNIT, 10-ADJUST_CODED, 11-TIME_PERIOD, 12-OBS_VALUE, 13-COLLECTION, 14-TIME_FORMAT, 15-AVAILABILITY, 16-BREAKS, 17-COLLECTION_DETAIL, 18-COVERAGE, 19-DATA_COMP, 20-DECIMALS, 21-DOC_METHOD, 22-MEASURE_DETAIL, 23-META_UPDATE, 24-PUBLICATIONS, 25-TITLE_GRP, 26-TITLE_GRP_COMPL, 27-TITLE_GRP_NAT, 28-UNIT_MEASURE, 29-UNIT_MULT, 30-OBS_CONF, 31-OBS_PRE_BREAK, 32-OBS_STATUS
    $metaRow = 'dataflow,"BIS:WS_DPP(1.0)",I,,"US: United States","0: Whole country","1: All types of dwellings","0: All","0: Central bank","0: Per dwelling","0: Non seasonally adjusted",,,,,"A: All users",,,"Covers all dwellings.","Test compilation.","4: Four",,,,,"US test series",,,"628: Index, 2010 = 100","0: Units",,,';

    // Data rows: 33 columns, TIME_PERIOD(11) and OBS_VALUE(12) filled
    $dataRow1 = 'dataflow,"BIS:WS_DPP(1.0)",I,"Q: Quarterly","US: United States","0: Whole country","1: All types of dwellings","0: All","0: Central bank","0: Per dwelling","0: Non seasonally adjusted",2020-Q1,150.1234,,,,,,,,,,,,,,,,,,,,';
    $dataRow2 = 'dataflow,"BIS:WS_DPP(1.0)",I,"Q: Quarterly","US: United States","0: Whole country","1: All types of dwellings","0: All","0: Central bank","0: Per dwelling","0: Non seasonally adjusted",2020-Q2,155.5678,,,,,,,,,,,,,,,,,,,,';

    $csv = implode("\n", [$header, $metaRow, $dataRow1, $dataRow2]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'dpp_import_test_');
    file_put_contents($tmpFile, $csv);

    return $tmpFile;
}

function mockDppBulkFileSource(string $csvPath, int $times = 1): void
{
    $mock = Mockery::mock(BulkFileSource::class);
    $mock->shouldReceive('download')->times($times)->andReturn($csvPath);
    app()->instance(BulkFileSource::class, $mock);
}

it('imports DPP data with series metadata from bulk CSV', function (): void {
    $csvPath = createDppTestCsv();
    mockDppBulkFileSource($csvPath);

    $this->artisan('real-estate:import-dpp')
        ->assertSuccessful();

    expect(DppSeries::count())->toBe(1);
    expect(DppObservation::count())->toBe(2);

    $series = DppSeries::first();
    expect($series->country_code)->toBe('US');
    expect($series->unit_measure)->toBe('628: Index, 2010 = 100');
    expect($series->coverage)->toBe('Covers all dwellings.');

    $obs = DppObservation::where('period', '2020-Q1')->first();
    expect($obs)->not->toBeNull();
    expect((float) $obs->value)->toBe(150.1234);
    expect($obs->series_id)->toBe($series->id);

    unlink($csvPath);
});

it('is idempotent — repeated import does not create duplicates', function (): void {
    $csvPath = createDppTestCsv();
    mockDppBulkFileSource($csvPath, 2);

    $this->artisan('real-estate:import-dpp')->assertSuccessful();
    expect(DppSeries::count())->toBe(1);
    expect(DppObservation::count())->toBe(2);

    $this->artisan('real-estate:import-dpp')->assertSuccessful();
    expect(DppSeries::count())->toBe(1);
    expect(DppObservation::count())->toBe(2);

    unlink($csvPath);
});
