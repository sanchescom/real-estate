<?php

declare(strict_types=1);

namespace Database\Factories\RealEstate;

use App\RealEstate\Infrastructure\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Country> */
final class CountryFactory extends Factory
{
    protected $model = Country::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->countryCode(),
            'name' => fake()->country(),
            'has_spp' => false,
            'has_dpp' => false,
        ];
    }

    public function withSpp(): self
    {
        return $this->state(['has_spp' => true]);
    }

    public function withDpp(): self
    {
        return $this->state(['has_dpp' => true]);
    }

    public function withBoth(): self
    {
        return $this->state(['has_spp' => true, 'has_dpp' => true]);
    }
}
