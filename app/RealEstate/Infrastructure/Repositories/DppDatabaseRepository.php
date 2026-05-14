<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Data\DppQuery;
use App\RealEstate\Domain\Queries\Contracts\DppRepository;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\DppObservation;
use App\RealEstate\Infrastructure\Models\DppSeries;
use Illuminate\Database\Eloquent\Builder;

final readonly class DppDatabaseRepository implements DppRepository
{
    public function findByCountry(DppQuery $query): array
    {
        $builder = $this->baseQuery($query->countryCode);

        $this->applyFilters($builder, $query->filters);
        $this->applySorting($builder, $query->sort);

        $total = $builder->count();

        $data = $builder->offset($query->offset)
            ->limit($query->limit)
            ->get()
            ->map(fn (DppObservation $obs): array => $this->mapObservation($obs))
            ->values()
            ->all();

        /** @var list<array<string, mixed>> $data */
        return ['data' => $data, 'total' => $total];
    }

    public function seriesForCountry(string $countryCode): array
    {
        $list = DppSeries::where('country_code', $countryCode)
            ->get()
            ->map(fn (DppSeries $s): array => [
                'id' => $s->id,
                'covered_area' => $s->covered_area,
                'property_type' => $s->property_type,
                'vintage' => $s->vintage,
                'compiling_org' => $s->compiling_org,
                'priced_unit' => $s->priced_unit,
                'seasonal_adj' => $s->seasonal_adj,
                'unit_measure' => $s->unit_measure,
                'title' => $s->title,
            ])
            ->values()
            ->all();

        /** @var list<array<string, mixed>> $list */
        return $list;
    }

    public function countryExists(string $countryCode): bool
    {
        return Country::where('code', $countryCode)->exists();
    }

    /**
     * @return Builder<DppObservation>
     */
    private function baseQuery(string $countryCode): Builder
    {
        return DppObservation::query()
            ->join('dpp_series', 'dpp_observations.series_id', '=', 'dpp_series.id')
            ->where('dpp_series.country_code', $countryCode)
            ->select([
                'dpp_observations.period',
                'dpp_observations.value',
                'dpp_observations.frequency',
                'dpp_series.covered_area',
                'dpp_series.property_type',
                'dpp_series.vintage',
                'dpp_series.unit_measure',
            ]);
    }

    /**
     * @param  Builder<DppObservation>  $builder
     * @param  array<string, string>  $filters
     */
    private function applyFilters(Builder $builder, array $filters): void
    {
        if (isset($filters['area'])) {
            $builder->whereRaw('dpp_series.covered_area = ?', [$filters['area']]);
        }

        if (isset($filters['property_type'])) {
            $builder->whereRaw('dpp_series.property_type = ?', [$filters['property_type']]);
        }

        if (isset($filters['vintage'])) {
            $builder->whereRaw('dpp_series.vintage = ?', [$filters['vintage']]);
        }

        if (isset($filters['freq'])) {
            $builder->whereRaw('dpp_observations.frequency = ?', [$filters['freq']]);
        }

        if (isset($filters['from'])) {
            $builder->whereRaw('dpp_observations.period >= ?', [$filters['from']]);
        }

        if (isset($filters['to'])) {
            $builder->whereRaw('dpp_observations.period <= ?', [$filters['to']]);
        }
    }

    /**
     * @param  Builder<DppObservation>  $builder
     */
    private function applySorting(Builder $builder, ?string $sort): void
    {
        $column = 'dpp_observations.period';
        $direction = 'asc';

        if ($sort !== null) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $col = ltrim($sort, '-');

            if ($col === 'value') {
                $column = 'dpp_observations.value';
            }
        }

        $builder->orderBy($column, $direction);
    }

    /** @return array<string, mixed> */
    private function mapObservation(DppObservation $obs): array
    {
        /** @var string $period */
        $period = $obs->getAttribute('period');
        /** @var string $value */
        $value = $obs->getAttribute('value');

        return [
            'period' => $period,
            'value' => (float) $value,
            'frequency' => $obs->getAttribute('frequency'),
            'covered_area' => $obs->getAttribute('covered_area'),
            'property_type' => $obs->getAttribute('property_type'),
            'vintage' => $obs->getAttribute('vintage'),
            'unit_measure' => $obs->getAttribute('unit_measure'),
        ];
    }
}
