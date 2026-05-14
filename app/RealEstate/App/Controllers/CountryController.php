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
use App\Shared\App\Pagination;
use App\Shared\App\PaginationContext;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class CountryController
{
    public function __construct(
        private ApiResponse $response,
        private CsvResponse $csv,
        private Pagination $pagination,
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
        $links = $this->pagination->links(new PaginationContext(
            route('real-estate.countries'), $query->offset, $query->limit, $total, $query->linkParams(),
        ));

        return $this->respond($result, $links);
    }

    public function show(
        ShowCountrySppRequest $request,
        GetCountrySpp $getCountrySpp,
    ): JsonResponse|StreamedResponse {
        $query = $this->buildShowQuery($request);
        $result = $getCountrySpp($query);

        if ($result === null) {
            return $this->response->error('Not Found', 404, "Country '{$query->countryCode}' not found.");
        }

        if ($request->validated('fmt') === 'csv') {
            return $this->csv->create($result['data'], "spp_{$query->countryCode}.csv");
        }

        /** @var int $total */
        $total = $result['meta']['total'];
        $links = $this->pagination->links(new PaginationContext(
            route('real-estate.show', $query->countryCode), $query->offset, $query->limit, $total, $query->linkParams(),
        ));

        return $this->respond($result, $links);
    }

    /**
     * @param  array{data: list<array<string, mixed>>, meta: array<string, mixed>}  $result
     * @param  array{next: ?string, prev: ?string}  $links
     */
    private function respond(array $result, array $links): JsonResponse
    {
        return $this->response->data($result['data'], meta: $result['meta'], links: $links)
            ->header('Cache-Control', RealEstateConstants::CACHE_CONTROL_HEADER)
            ->header('ETag', '"'.md5((string) json_encode($result['data'], JSON_THROW_ON_ERROR)).'"');
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
}
