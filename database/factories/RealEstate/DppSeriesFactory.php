<?php

declare(strict_types=1);

namespace Database\Factories\RealEstate;

use App\RealEstate\Infrastructure\Models\DppSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DppSeries> */
final class DppSeriesFactory extends Factory
{
    protected $model = DppSeries::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'country_code' => 'US',
            'covered_area' => '0',
            'property_type' => '1',
            'vintage' => '0',
            'compiling_org' => '0',
            'priced_unit' => '0',
            'seasonal_adj' => '0',
            'unit_measure' => '628: Index, 2010 = 100',
            'title' => fake()->sentence(),
            'coverage' => null,
            'data_compilation' => null,
        ];
    }

    public function forCountry(string $code): self
    {
        return $this->state(['country_code' => $code]);
    }
}
