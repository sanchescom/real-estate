<?php

declare(strict_types=1);

use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\SppObservation;

beforeEach(function (): void {
    Country::factory()->create([
        'code' => 'US',
        'name' => 'United States',
        'has_spp' => true,
    ]);

    SppObservation::factory()->forCountry('US')->nominal()->index()
        ->forPeriod('2020-Q1')->create(['value' => 208.2134]);
    SppObservation::factory()->forCountry('US')->nominal()->index()
        ->forPeriod('2020-Q2')->create(['value' => 211.4567]);
    SppObservation::factory()->forCountry('US')->nominal()->index()
        ->forPeriod('2020-Q3')->create(['value' => 217.8901]);
    SppObservation::factory()->forCountry('US')->real()->index()
        ->forPeriod('2020-Q1')->create(['value' => 152.0000]);
    SppObservation::factory()->forCountry('US')->nominal()->yoy()
        ->forPeriod('2020-Q1')->create(['value' => 5.1234]);
});

it('returns SPP data with meta', function (): void {
    $this->getJson('/api/v1/real-estate/US', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertJsonPath('meta.country_code', 'US')
        ->assertJsonPath('meta.source', 'BIS')
        ->assertJsonPath('meta.frequency', 'quarterly')
        ->assertJsonStructure([
            'data' => [['period', 'value']],
            'meta' => ['country_code', 'source', 'total'],
            'links',
        ]);
});

it('filters by type=nominal', function (): void {
    $this->getJson(
        '/api/v1/real-estate/US?filter[type]=nominal',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonPath('meta.type', 'nominal')
        ->assertJsonPath('meta.total', 4);
});

it('filters by metric=index', function (): void {
    $this->getJson(
        '/api/v1/real-estate/US?filter[metric]=index',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonPath('meta.metric', 'index')
        ->assertJsonPath('meta.total', 4);
});

it('filters by from and to period', function (): void {
    $this->getJson(
        '/api/v1/real-estate/US?filter[type]=nominal&filter[metric]=index&filter[from]=2020-Q1&filter[to]=2020-Q2',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.period', '2020-Q1')
        ->assertJsonPath('data.0.value', 208.2134);
});

it('returns 404 for non-existent country', function (): void {
    $this->getJson('/api/v1/real-estate/ZZ', ['X-API-Key' => 'test-key-1'])
        ->assertStatus(404)
        ->assertJsonPath('errors.0.title', 'Not Found');
});

it('returns 422 for invalid filter type', function (): void {
    $this->getJson(
        '/api/v1/real-estate/US?filter[type]=invalid',
        ['X-API-Key' => 'test-key-1'],
    )
        ->assertStatus(422);
});

it('returns Cache-Control and ETag headers', function (): void {
    $this->getJson('/api/v1/real-estate/US', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertHeader('ETag');
});
