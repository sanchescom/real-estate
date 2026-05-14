<?php

declare(strict_types=1);

namespace App\RealEstate\Domain\Data;

final readonly class DppSeriesData
{
    public function __construct(
        public string $countryCode,
        public string $countryName,
        public string $coveredArea,
        public string $propertyType,
        public string $vintage,
        public string $compilingOrg,
        public string $pricedUnit,
        public string $seasonalAdj,
        public string $unitMeasure,
        public ?string $title = null,
        public ?string $coverage = null,
        public ?string $dataCompilation = null,
    ) {}

    /** @param array<string, string|int|float|null> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['country_code'] ?? ''),
            countryName: (string) ($data['country_name'] ?? ''),
            coveredArea: (string) ($data['covered_area'] ?? ''),
            propertyType: (string) ($data['property_type'] ?? ''),
            vintage: (string) ($data['vintage'] ?? ''),
            compilingOrg: (string) ($data['compiling_org'] ?? ''),
            pricedUnit: (string) ($data['priced_unit'] ?? ''),
            seasonalAdj: (string) ($data['seasonal_adj'] ?? ''),
            unitMeasure: (string) ($data['unit_measure'] ?? ''),
            title: isset($data['title']) ? (string) $data['title'] : null,
            coverage: isset($data['coverage']) ? (string) $data['coverage'] : null,
            dataCompilation: isset($data['data_compilation']) ? (string) $data['data_compilation'] : null,
        );
    }

    public function dimensionKey(): string
    {
        return implode('|', [
            $this->countryCode,
            $this->coveredArea,
            $this->propertyType,
            $this->vintage,
            $this->compilingOrg,
            $this->pricedUnit,
            $this->seasonalAdj,
        ]);
    }
}
