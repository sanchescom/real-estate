<?php

declare(strict_types=1);

use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;

beforeEach(function (): void {
    Country::factory()->create(['code' => 'AU', 'name' => 'Australia', 'has_dpp' => true]);

    $this->series = DppSeries::factory()->forCountry('AU')->create([
        'covered_area' => '0',
        'property_type' => '1',
        'vintage' => '0',
        'unit_measure' => '628: Index, 2010 = 100',
        'title' => 'Australia - All dwellings, whole country',
    ]);

    DppObservation::factory()->forSeries($this->series)
        ->forPeriod('2020-Q1')->create(['value' => 150.0, 'frequency' => 'Q']);
    DppObservation::factory()->forSeries($this->series)
        ->forPeriod('2020-Q2')->create(['value' => 155.0, 'frequency' => 'Q']);
});

it('returns DPP data with meta', function (): void {
    $this->getJson('/api/v1/real-estate/AU/detailed', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.country_code', 'AU')
        ->assertJsonPath('meta.dataset', 'DPP')
        ->assertJsonPath('data.0.unit_measure', '628: Index, 2010 = 100');
});

it('filters by area and property_type', function (): void {
    $otherSeries = DppSeries::factory()->forCountry('AU')->create([
        'covered_area' => '2',
        'property_type' => '8',
        'vintage' => '0',
        'compiling_org' => '1',
        'unit_measure' => '628: Index, 2010 = 100',
    ]);
    DppObservation::factory()->forSeries($otherSeries)
        ->forPeriod('2020-Q1')->create(['frequency' => 'Q']);

    $this->getJson(
        '/api/v1/real-estate/AU/detailed?filter[area]=0&filter[property_type]=1',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson(
        '/api/v1/real-estate/AU/detailed?filter[area]=2&filter[property_type]=8',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters by from period', function (): void {
    $this->getJson(
        '/api/v1/real-estate/AU/detailed?filter[from]=2020-Q2',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.period', '2020-Q2');
});

it('returns available series for country', function (): void {
    $this->getJson('/api/v1/real-estate/AU/detailed/series', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.covered_area', '0')
        ->assertJsonPath('data.0.property_type', '1')
        ->assertJsonPath('data.0.unit_measure', '628: Index, 2010 = 100')
        ->assertJsonPath('meta.total', 1);
});

it('returns 404 for non-existent country', function (): void {
    $this->getJson('/api/v1/real-estate/ZZ/detailed', ['X-API-Key' => 'test-key-1'])
        ->assertStatus(404);

    $this->getJson('/api/v1/real-estate/ZZ/detailed/series', ['X-API-Key' => 'test-key-1'])
        ->assertStatus(404);
});

it('shows status with correct counts', function (): void {
    $this->artisan('real-estate:status')
        ->assertSuccessful();
});
