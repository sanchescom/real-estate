<?php

declare(strict_types=1);

use App\RealEstate\Domain\Commands\Contracts\BulkFileSource;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\SppObservation;

function createSppTestCsv(): string
{
    $csv = <<<'CSV'
STRUCTURE,STRUCTURE_ID,ACTION,"FREQ:Frequency","REF_AREA:Reference area","VALUE:Value","UNIT_MEASURE:Unit of measure","TIME_PERIOD:Time period or range","OBS_VALUE:Observation Value","UNIT_MULT:Unit Multiplier","BREAKS:Breaks","COVERAGE:Coverage","TITLE_TS:Title (tseries level)","OBS_STATUS:Observation Status","OBS_CONF:Observation confidentiality","OBS_PRE_BREAK:Pre-Break Observation"
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","N: Nominal","628: Index, 2010 = 100",2020-Q1,208.2134,"0: Units",,,,A: Normal value,"F: Free",
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","US: United States","R: Real","628: Index, 2010 = 100",2020-Q1,152.3456,"0: Units",,,,A: Normal value,"F: Free",
dataflow,"BIS:WS_SPP(1.0)",I,"Q: Quarterly","DE: Germany","N: Nominal","771: Year-on-year changes, in per cent",2020-Q1,5.1234,"0: Units",,,,A: Normal value,"F: Free",
CSV;

    $tmpFile = tempnam(sys_get_temp_dir(), 'spp_import_test_');
    file_put_contents($tmpFile, $csv);

    return $tmpFile;
}

function mockBulkFileSource(string $csvPath): void
{
    $mock = Mockery::mock(BulkFileSource::class);
    $mock->shouldReceive('download')->once()->andReturn($csvPath);
    app()->instance(BulkFileSource::class, $mock);
}

it('imports SPP data from bulk CSV', function (): void {
    $csvPath = createSppTestCsv();
    mockBulkFileSource($csvPath);

    $this->artisan('real-estate:import-spp')
        ->assertSuccessful();

    expect(SppObservation::count())->toBe(3);
    expect(Country::count())->toBe(2);

    $us = SppObservation::where('country_code', 'US')
        ->where('value_type', 'N')
        ->where('unit_measure', '628')
        ->where('period', '2020-Q1')
        ->first();

    expect($us)->not->toBeNull();
    expect((float) $us->value)->toBe(208.2134);

    unlink($csvPath);
});

it('is idempotent — repeated import does not create duplicates', function (): void {
    $csvPath = createSppTestCsv();

    $mock = Mockery::mock(BulkFileSource::class);
    $mock->shouldReceive('download')->twice()->andReturn($csvPath);
    app()->instance(BulkFileSource::class, $mock);

    $this->artisan('real-estate:import-spp')->assertSuccessful();
    expect(SppObservation::count())->toBe(3);

    $this->artisan('real-estate:import-spp')->assertSuccessful();
    expect(SppObservation::count())->toBe(3);

    unlink($csvPath);
});

it('updates countries has_spp flag', function (): void {
    $csvPath = createSppTestCsv();
    mockBulkFileSource($csvPath);

    $this->artisan('real-estate:import-spp')->assertSuccessful();

    $us = Country::where('code', 'US')->first();
    expect($us)->not->toBeNull();
    expect($us->name)->toBe('United States');
    expect($us->has_spp)->toBeTrue();

    $de = Country::where('code', 'DE')->first();
    expect($de)->not->toBeNull();
    expect($de->has_spp)->toBeTrue();

    unlink($csvPath);
});
