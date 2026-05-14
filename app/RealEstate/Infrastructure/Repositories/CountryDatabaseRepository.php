<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Repositories;

use App\RealEstate\Domain\Contracts\CountryRepository;
use App\RealEstate\Domain\Data\PaginationQuery;
use App\RealEstate\Infrastructure\Models\Country;
use Illuminate\Database\Eloquent\Builder;

final readonly class CountryDatabaseRepository implements CountryRepository
{
    #[\Override]
    public function list(PaginationQuery $query): array
    {
        $builder = Country::query();

        $this->applySorting($builder, $query->sort);

        $total = $builder->count();

        $data = $builder->offset($query->offset)
            ->limit($query->limit)
            ->get()
            ->map(fn (Country $c): array => [
                'code' => $c->code,
                'name' => $c->name,
                'has_spp' => $c->has_spp,
                'has_dpp' => $c->has_dpp,
            ])
            ->values()
            ->all();

        /** @var list<array<string, mixed>> $data */
        return ['data' => $data, 'total' => $total];
    }

    #[\Override]
    public function upsertCountries(array $countries, string $dataset): void
    {
        $flag = $dataset === 'SPP' ? 'has_spp' : 'has_dpp';

        $rows = [];
        foreach ($countries as $code => $name) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                $flag => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Country::upsert($rows, ['code'], ['name', $flag, 'updated_at']);
    }

    /**
     * @param  Builder<Country>  $builder
     */
    private function applySorting($builder, ?string $sort): void
    {
        if ($sort === null) {
            $builder->orderBy('code');

            return;
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (in_array($column, ['code', 'name'], true)) {
            $builder->orderBy($column, $direction);
        }
    }
}
