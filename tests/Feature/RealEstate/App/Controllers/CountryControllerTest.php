<?php

declare(strict_types=1);

use App\RealEstate\Infrastructure\Models\Country;

it('returns list of countries with has_spp and has_dpp', function (): void {
    Country::factory()->create(['code' => 'US', 'name' => 'United States', 'has_spp' => true, 'has_dpp' => true]);
    Country::factory()->create(['code' => 'DE', 'name' => 'Germany', 'has_spp' => true, 'has_dpp' => false]);

    $this->getJson('/api/v1/real-estate/countries', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.code', 'DE')
        ->assertJsonPath('data.0.has_spp', true)
        ->assertJsonPath('data.1.code', 'US')
        ->assertJsonPath('data.1.has_dpp', true);
});

it('returns empty data when no countries exist', function (): void {
    $this->getJson('/api/v1/real-estate/countries', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0);
});

it('returns 403 without API key', function (): void {
    $this->getJson('/api/v1/real-estate/countries')
        ->assertStatus(403);
});

it('paginates with offset and limit', function (): void {
    Country::factory()->create(['code' => 'AT', 'name' => 'Austria']);
    Country::factory()->create(['code' => 'DE', 'name' => 'Germany']);
    Country::factory()->create(['code' => 'US', 'name' => 'United States']);

    $response = $this->getJson(
        '/api/v1/real-estate/countries?page[offset]=0&page[limit]=2',
        ['X-API-Key' => 'test-key-1'],
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.offset', 0)
        ->assertJsonPath('meta.limit', 2);

    $next = $response->json('links.next');
    expect($next)->toBeString();
    expect($next)->toContain('page%5Boffset%5D=2');
});

it('returns Cache-Control and ETag headers', function (): void {
    Country::factory()->create(['code' => 'US']);

    $this->getJson('/api/v1/real-estate/countries', ['X-API-Key' => 'test-key-1'])
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertHeader('ETag');
});
