<?php

declare(strict_types=1);

namespace App\RealEstate\App\Controllers;

use App\RealEstate\App\Requests\ListCountriesRequest;
use App\RealEstate\Domain\Data\PaginationQuery;
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
        $query = $this->buildQuery($request);
        $result = $listCountries($query);

        if ($request->validated('fmt') === 'csv') {
            return $this->csv->create($result['data'], 'countries.csv');
        }

        /** @var int $total */
        $total = $result['meta']['total'];
        $links = $this->buildPaginationLinks($query, $total, $request);

        return $this->response->data($result['data'], meta: $result['meta'], links: $links)
            ->header('Cache-Control', RealEstateConstants::CACHE_CONTROL_HEADER)
            ->header('ETag', '"'.md5((string) json_encode($result['data'])).'"');
    }

    private function buildQuery(ListCountriesRequest $request): PaginationQuery
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

    /**
     * @return array<string, string|null>
     */
    private function buildPaginationLinks(
        PaginationQuery $query,
        int $total,
        ListCountriesRequest $request,
    ): array {
        $nextParams = array_filter([
            'page[offset]' => $query->offset + $query->limit,
            'page[limit]' => $query->limit,
            'sort' => $query->sort,
            'fmt' => $request->validated('fmt'),
        ]);

        $prevParams = array_filter([
            'page[offset]' => max(0, $query->offset - $query->limit),
            'page[limit]' => $query->limit,
            'sort' => $query->sort,
        ]);

        $base = '/api/v1/real-estate/countries';

        return [
            'next' => $query->offset + $query->limit < $total
                ? $base.'?'.http_build_query($nextParams)
                : null,
            'prev' => $query->offset > 0
                ? $base.'?'.http_build_query($prevParams)
                : null,
        ];
    }
}
