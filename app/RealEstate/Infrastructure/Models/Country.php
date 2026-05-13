<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Models;

use Database\Factories\RealEstate\CountryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    protected $fillable = [
        'code',
        'name',
        'has_spp',
        'has_dpp',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'has_spp' => 'boolean',
            'has_dpp' => 'boolean',
        ];
    }

    /** @param Builder<Country> $query */
    public function scopeWithSpp(Builder $query): void
    {
        $query->where('has_spp', true);
    }

    /** @param Builder<Country> $query */
    public function scopeWithDpp(Builder $query): void
    {
        $query->where('has_dpp', true);
    }
}
