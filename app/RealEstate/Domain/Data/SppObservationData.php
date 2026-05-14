<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

final readonly class SppObservationData
{
    public function __construct(
        public string $countryCode,
        public string $countryName,
        public string $valueType,
        public string $unitMeasure,
        public string $period,
        public string $value,
        public ?string $obsStatus = null,
    ) {}

    /** @param array<string, string|int|float|null> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['country_code'] ?? ''),
            countryName: (string) ($data['country_name'] ?? ''),
            valueType: (string) ($data['value_type'] ?? ''),
            unitMeasure: (string) ($data['unit_measure'] ?? ''),
            period: (string) ($data['period'] ?? ''),
            value: (string) ($data['value'] ?? ''),
            obsStatus: isset($data['obs_status']) ? (string) $data['obs_status'] : null,
        );
    }

    public function isValid(): bool
    {
        if ($this->countryCode === '' || $this->period === '' || $this->value === '') {
            return false;
        }

        if (! preg_match('/^\d{4}-Q[1-4]$/', $this->period)) {
            return false;
        }

        return is_numeric($this->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(): array
    {
        return [
            'country_code' => $this->countryCode,
            'value_type' => $this->valueType,
            'unit_measure' => $this->unitMeasure,
            'period' => $this->period,
            'value' => $this->value,
            'obs_status' => $this->obsStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
