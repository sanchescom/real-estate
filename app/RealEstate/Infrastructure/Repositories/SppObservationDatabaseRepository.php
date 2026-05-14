<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Contracts\SppObservationRepository;
use App\RealEstate\Domain\Data\SppQuery;
use App\RealEstate\Domain\Enums\UnitMeasure;
use App\RealEstate\Domain\Enums\ValueType;
use App\RealEstate\Infrastructure\Models\Country;
use App\RealEstate\Infrastructure\Models\SppObservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;

final readonly class SppObservationDatabaseRepository implements SppObservationRepository
{
    #[\Override]
    public function findByCountry(SppQuery $query): array
    {
        $builder = SppObservation::query()
            ->where('country_code', $query->countryCode);

        $this->applyFilters($builder, $query->filters);
        $this->applySorting($builder, $query->sort);

        $total = $builder->count();

        $data = $builder->offset($query->offset)
            ->limit($query->limit)
            ->get()
            ->map(fn (SppObservation $obs): array => [
                'period' => $obs->period,
                'value' => (float) $obs->value,
            ])
            ->values()
            ->all();

        /** @var list<array<string, mixed>> $data */
        return ['data' => $data, 'total' => $total];
    }

    #[\Override]
    public function countryExists(string $countryCode): bool
    {
        return Country::where('code', $countryCode)->exists();
    }

    #[\Override]
    public function countryName(string $countryCode): ?string
    {
        /** @var ?string */
        return Country::where('code', $countryCode)->value('name');
    }

    #[\Override]
    public function upsertObservations(array $chunk): void
    {
        $now = now();
        $stamped = array_map(
            fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
            $chunk,
        );

        DB::transaction(function () use ($stamped): void {
            SppObservation::upsert(
                $stamped,
                ['country_code', 'value_type', 'unit_measure', 'period'],
                ['value', 'obs_status', 'updated_at'],
            );
        });
    }

    #[\Override]
    public function invalidateCache(): void
    {
        Cache::tags(['real-estate'])->flush();
        ResponseCache::clear();
    }

    /**
     * @param  Builder<SppObservation>  $builder
     * @param  array<string, string>  $filters
     */
    private function applyFilters(Builder $builder, array $filters): void
    {
        if (isset($filters['type'])) {
            $builder->where('value_type', ValueType::fromLabel($filters['type'])->value);
        }

        if (isset($filters['metric'])) {
            $builder->where('unit_measure', UnitMeasure::fromLabel($filters['metric'])->value);
        }

        if (isset($filters['from'])) {
            $builder->where('period', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $builder->where('period', '<=', $filters['to']);
        }
    }

    /**
     * @param  Builder<SppObservation>  $builder
     */
    private function applySorting(Builder $builder, ?string $sort): void
    {
        $column = 'period';
        $direction = 'asc';

        if ($sort !== null) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $col = ltrim($sort, '-');

            if (in_array($col, ['period', 'value'], true)) {
                $column = $col;
            }
        }

        $builder->orderBy($column, $direction);
    }
}
