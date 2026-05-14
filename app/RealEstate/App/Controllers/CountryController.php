<?php

declare(strict_types=1);

namespace App\RealEstate\App\Controllers;

use App\RealEstate\App\Requests\ListCountriesRequest;
use App\RealEstate\App\Requests\ShowCountrySppRequest;
use App\RealEstate\Domain\Data\PaginationQuery;
use App\RealEstate\Domain\Data\SppQuery;
use App\RealEstate\Domain\Queries\Actions\GetCountrySpp;
use App\RealEstate\Domain\Queries\Actions\ListCountries;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\App\ApiResponse;
use App\Shared\App\CsvResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CountryController
{
    public function __construct(
        private readonly ApiResponse $response,
        private readonly CsvResponse $csv,
    ) {}

    public function index(
        ListCountriesRequest $request,
        ListCountries $listCountries,
    ): JsonResponse|StreamedResponse {
        $query = $this->buildIndexQuery($request);
        $result = $listCountries($query);

        if ($request->validated('fmt') === 'csv') {
            return $this->csv->create($result['data'], 'countries.csv');
        }

        /** @var int $total */
        $total = $result['meta']['total'];
        $links = $this->buildIndexLinks($query, $total);

        return $this->respond($result, $links);
    }

    public function show(
        ShowCountrySppRequest $request,
        GetCountrySpp $getCountrySpp,
    ): JsonResponse|StreamedResponse {
        $query = $this->buildShowQuery($request);
        $result = $getCountrySpp($query);

        if ($result === null) {
            return $this->response->error(
                'Not Found',
                404,
                "Country '{$query->countryCode}' not found.",
            );
        }

        if ($request->validated('fmt') === 'csv') {
            return $this->csv->create($result['data'], "spp_{$query->countryCode}.csv");
        }

        /** @var int $total */
        $total = $result['meta']['total'];
        $links = $this->buildShowLinks($query, $total);

        return $this->respond($result, $links);
    }

    /**
     * @param  array{data: list<array<string, mixed>>, meta: array<string, mixed>}  $result
     * @param  array<string, string|null>  $links
     */
    private function respond(array $result, array $links): JsonResponse
    {
        return $this->response->data($result['data'], meta: $result['meta'], links: $links)
            ->header('Cache-Control', RealEstateConstants::CACHE_CONTROL_HEADER)
            ->header('ETag', '"'.md5((string) json_encode($result['data'])).'"');
    }

    private function buildIndexQuery(ListCountriesRequest $request): PaginationQuery
    {
        /** @var array{offset?: int, limit?: int} $page */
        $page = $request->validated('page', []);
        /** @var string|null $sort */
        $sort = $request->validated('sort');

        return new PaginationQuery(
            offset: (int) ($page['offset'] ?? 0),
            limit: (int) ($page['limit'] ?? RealEstateConstants::DEFAULT_PAGE_LIMIT),
            sort: $sort,
        );
    }

    private function buildShowQuery(ShowCountrySppRequest $request): SppQuery
    {
        /** @var array{offset?: int, limit?: int} $page */
        $page = $request->validated('page', []);
        /** @var string $code */
        $code = $request->validated('code');
        /** @var array<string, string> $filters */
        $filters = $request->validated('filter', []);
        /** @var string|null $sort */
        $sort = $request->validated('sort');

        return new SppQuery(
            countryCode: $code,
            filters: $filters,
            sort: $sort,
            offset: (int) ($page['offset'] ?? 0),
            limit: (int) ($page['limit'] ?? RealEstateConstants::DEFAULT_PAGE_LIMIT),
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function buildIndexLinks(PaginationQuery $query, int $total): array
    {
        if ($query->offset + $query->limit >= $total) {
            return ['next' => null, 'prev' => null];
        }

        $nextParams = array_filter([
            'page[offset]' => $query->offset + $query->limit,
            'page[limit]' => $query->limit,
            'sort' => $query->sort,
        ]);

        return [
            'next' => '/api/v1/real-estate/countries?'.http_build_query($nextParams),
            'prev' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function buildShowLinks(SppQuery $query, int $total): array
    {
        if ($query->offset + $query->limit >= $total) {
            return ['next' => null, 'prev' => null];
        }

        $nextParams = array_filter([
            'page[offset]' => $query->offset + $query->limit,
            'page[limit]' => $query->limit,
            'sort' => $query->sort,
        ]);

        foreach ($query->filters as $key => $value) {
            $nextParams["filter[{$key}]"] = $value;
        }

        $base = "/api/v1/real-estate/{$query->countryCode}";

        return [
            'next' => $base.'?'.http_build_query($nextParams),
            'prev' => null,
        ];
    }
}
