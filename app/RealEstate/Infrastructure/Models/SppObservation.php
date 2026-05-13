<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Models;

use App\RealEstate\Domain\Enums\UnitMeasure;
use App\RealEstate\Domain\Enums\ValueType;
use Database\Factories\RealEstate\SppObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SppObservation extends Model
{
    /** @use HasFactory<SppObservationFactory> */
    use HasFactory;

    protected static function newFactory(): SppObservationFactory
    {
        return SppObservationFactory::new();
    }

    protected $fillable = [
        'country_code',
        'value_type',
        'unit_measure',
        'period',
        'value',
        'obs_status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value_type' => ValueType::class,
            'unit_measure' => UnitMeasure::class,
            'value' => 'decimal:4',
        ];
    }
}
