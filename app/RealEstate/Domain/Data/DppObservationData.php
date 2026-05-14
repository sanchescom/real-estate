<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

final readonly class DppObservationData
{
    public function __construct(
        public string $dimensionKey,
        public string $countryCode,
        public string $countryName,
        public string $frequency,
        public string $period,
        public string $value,
        public ?string $obsStatus = null,
    ) {}

    /** @param array<string, string|int|float|null> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            dimensionKey: (string) ($data['dimension_key'] ?? ''),
            countryCode: (string) ($data['country_code'] ?? ''),
            countryName: (string) ($data['country_name'] ?? ''),
            frequency: (string) ($data['frequency'] ?? ''),
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

        if (! preg_match('/^(\d{4}-Q[1-4]|\d{4}-\d{2}|\d{4}|\d{4}-S[12])$/', $this->period)) {
            return false;
        }

        return is_numeric($this->value);
    }
}
