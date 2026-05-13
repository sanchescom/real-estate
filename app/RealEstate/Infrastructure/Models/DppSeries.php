<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Models;

use Database\Factories\RealEstate\DppSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DppSeries extends Model
{
    /** @use HasFactory<DppSeriesFactory> */
    use HasFactory;

    protected static function newFactory(): DppSeriesFactory
    {
        return DppSeriesFactory::new();
    }

    protected $fillable = [
        'country_code',
        'covered_area',
        'property_type',
        'vintage',
        'compiling_org',
        'priced_unit',
        'seasonal_adj',
        'unit_measure',
        'title',
        'coverage',
        'data_compilation',
    ];

    /** @return HasMany<DppObservation, $this> */
    public function observations(): HasMany
    {
        return $this->hasMany(DppObservation::class, 'series_id');
    }
}
