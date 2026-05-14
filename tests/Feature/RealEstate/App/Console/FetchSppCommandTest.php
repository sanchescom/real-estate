<?php

declare(strict_types=1);

use App\RealEstate\Domain\Commands\Contracts\SdmxApiSource;
use App\RealEstate\Infrastructure\Models\SppObservation;

function sppApiCsvResponse(): string
{
    return <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","VALUE:Value","UNIT_MEASURE:Unit of measure","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","UNIT_MULT:Unit Multiplier","BREAKS:Breaks","COVERAGE:Coverage","TITLE_TS:Title (tseries level)","OBS_STATUS:Observation Status","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation"
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",2024-Q3,275.1234,"0: Units",,,,A: Normal value,"F: Free",
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",2024-Q4,280.5678,"0: Units",,,,A: Normal value,"F: Free",
CSV;
}

it('fetches SPP updates from SDMX API', function (): void {
    $mock = Mockery::mock(SdmxApiSource::class);
    $mock->shouldReceive('fetchSpp')->once()->andReturn(sppApiCsvResponse());
    app()->instance(SdmxApiSource::class, $mock);

    $this->artisan('real-estate:fetch-spp')
        ->assertSuccessful();

    expect(SppObservation::count())->toBe(2);
});

it('upserts existing records on fetch', function (): void {
    SppObservation::create([
        'country_code' => 'US',
        'value_type' => 'N',
        'unit_measure' => '628',
        'period' => '2024-Q3',
        'value' => 100.0,
        'obs_status' => 'A',
    ]);

    $mock = Mockery::mock(SdmxApiSource::class);
    $mock->shouldReceive('fetchSpp')->once()->andReturn(sppApiCsvResponse());
    app()->instance(SdmxApiSource::class, $mock);

    $this->artisan('real-estate:fetch-spp')->assertSuccessful();

    expect(SppObservation::count())->toBe(2);

    $updated = SppObservation::where('period', '2024-Q3')->first();
    expect((float) $updated->value)->toBe(275.1234);
});
