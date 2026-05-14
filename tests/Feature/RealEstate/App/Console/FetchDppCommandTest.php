<?php

declare(strict_types=1);

use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;

it('fetches DPP updates from SDMX API', function (): void {
    // Pre-create a series (incremental fetch expects bulk import to have run first)
    $series = DppSeries::create([
        'country_code' => 'US',
        'covered_area' => '0',
        'property_type' => '1',
        'vintage' => '0',
        'compiling_org' => '0',
        'priced_unit' => '0',
        'seasonal_adj' => '0',
        'unit_measure' => '628: Index, 2010 = 100',
    ]);

    $csv = <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","COVERED_AREA:Covered area","RE_TYPE:Real estate type","RE_VINTAGE:Real estate vintage","COMPILING_ORG:Compiling agency","PRICED_UNIT:Priced unit","ADJUST_CODED:Seasonal adjustment","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","COLLECTION:Collection Indicator","TIME_FORMAT:Time Format","AVAILABILITY:Availability","BREAKS:Breaks","COLLECTION_DETAIL:Collection explanation detail","COVERAGE:Coverage","DATA_COMP:Data compilation","DECIMALS:Decimals","DOC_METHOD:Documentation on methodology","MEASURE_DETAIL:Unit of measure detail","META_UPDATE:Documentation date","PUBLICATIONS:Dissemination format - publications","TITLE_GRP:Title","TITLE_GRP_COMPL:Title complement","TITLE_GRP_NAT:National language title","UNIT_MEASURE:Unit of measure","UNIT_MULT:Unit Multiplier","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation","OBS_STATUS:Observation Status"
dataflow,"BIS:WS_DPP(1.0)",I,"Q: Quarterly","US: United States","0: Whole country","1: All types of dwellings","0: All","0: Central bank","0: Per dwelling","0: Non seasonally adjusted",2024-Q4,160.5678,,,,,,,,,,,,,,,,,,,
CSV;

    $mock = Mockery::mock(SdmxApiSource::class);
    $mock->shouldReceive('fetchDpp')->once()->andReturn($csv);
    app()->instance(SdmxApiSource::class, $mock);

    $this->artisan('real-estate:fetch-dpp')
        ->assertSuccessful();

    expect(DppObservation::count())->toBe(1);

    $obs = DppObservation::first();
    expect($obs->series_id)->toBe($series->id);
    expect((float) $obs->value)->toBe(160.5678);
});
