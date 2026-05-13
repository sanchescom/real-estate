<?php

declare(strict_types=1);

namespace App\RealEstate\Infrastructure\Models;

use App\RealEstate\Domain\Enums\Frequency;
use Database\Factories\RealEstate\DppObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DppObservation extends Model
{
    /** @use HasFactory<DppObservationFactory> */
    use HasFactory;

    protected static function newFactory(): DppObservationFactory
    {
        return DppObservationFactory::new();
    }

    protected $fillable = [
        'series_id',
        'frequency',
        'period',
        'value',
        'obs_status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'frequency' => Frequency::class,
            'value' => 'decimal:4',
            'series_id' => 'integer',
        ];
    }

    /** @return BelongsTo<DppSeries, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(DppSeries::class, 'series_id');
    }
}
