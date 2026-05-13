<?php

declare(strict_types=1);

namespace Database\Factories\RealEstate;

use App\RealEstate\Domain\Enums\UnitMeasure;
use App\RealEstate\Domain\Enums\ValueType;
use App\RealEstate\Infrastructure\Models\SppObservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SppObservation> */
final class SppObservationFactory extends Factory
{
    protected $model = SppObservation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = fake()->numberBetween(2000, 2025);
        $quarter = fake()->numberBetween(1, 4);

        return [
            'country_code' => 'US',
            'value_type' => fake()->randomElement(ValueType::cases())->value,
            'unit_measure' => fake()->randomElement(UnitMeasure::cases())->value,
            'period' => "{$year}-Q{$quarter}",
            'value' => fake()->randomFloat(4, 50, 300),
            'obs_status' => 'A',
        ];
    }

    public function nominal(): self
    {
        return $this->state(['value_type' => ValueType::Nominal->value]);
    }

    public function real(): self
    {
        return $this->state(['value_type' => ValueType::Real->value]);
    }

    public function index(): self
    {
        return $this->state(['unit_measure' => UnitMeasure::Index->value]);
    }

    public function yoy(): self
    {
        return $this->state(['unit_measure' => UnitMeasure::YearOnYear->value]);
    }

    public function forCountry(string $code): self
    {
        return $this->state(['country_code' => $code]);
    }

    public function forPeriod(string $period): self
    {
        return $this->state(['period' => $period]);
    }
}
