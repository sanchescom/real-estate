<?php

declare(strict_types=1);

namespace Database\Factories\RealEstate;

use App\RealEstate\Domain\Enums\Frequency;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DppObservation> */
final class DppObservationFactory extends Factory
{
    protected $model = DppObservation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = fake()->numberBetween(2000, 2025);
        $quarter = fake()->numberBetween(1, 4);

        return [
            'series_id' => DppSeries::factory(),
            'frequency' => Frequency::Quarterly->value,
            'period' => "{$year}-Q{$quarter}",
            'value' => fake()->randomFloat(4, 50, 300),
            'obs_status' => null,
        ];
    }

    public function forSeries(DppSeries $series): self
    {
        return $this->state(['series_id' => $series->id]);
    }

    public function forPeriod(string $period): self
    {
        return $this->state(['period' => $period]);
    }

    public function quarterly(): self
    {
        return $this->state(['frequency' => Frequency::Quarterly->value]);
    }

    public function annual(): self
    {
        return $this->state(['frequency' => Frequency::Annual->value]);
    }
}
