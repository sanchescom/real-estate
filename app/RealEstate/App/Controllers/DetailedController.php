<?php

declare(strict_types=1);

namespace App\RealEstate\App\Controllers;

use App\RealEstate\App\Requests\ListDppSeriesRequest;
use App\RealEstate\App\Requests\ShowCountryDppRequest;
use App\RealEstate\Domain\Data\DppQuery;
use App\RealEstate\Domain\Queries\Actions\GetCountryDpp;
use App\RealEstate\Domain\Queries\Actions\ListCountryDppSeries;
use App\RealEstate\Domain\RealEstateConstants;
use App\Shared\App\ApiResponse;
use App\Shared\App\CsvResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DetailedController
{
    public function __construct(
        private readonly ApiResponse $response,
        private readonly CsvResponse $csv,
    ) {}

    public function show(
        ShowCountryDppRequest $request,
        GetCountryDpp $getCountryDpp,
    ): JsonResponse|StreamedResponse {
        $query = $this->buildQuery($request);
        $result = $getCountryDpp($query);

        if ($result === null) {
            return $this->response->error(
                'Not Found',
                404,
                "Country '{$query->countryCode}' not found.",
            );
        }

        if ($request->validated('fmt') === 'csv') {
            return $this->csv->create($result['data'], "dpp_{$query->countryCode}.csv");
        }

        /** @var int $total */
        $total = $result['meta']['total'];
        $links = $this->buildPaginationLinks($query, $total);

        return $this->response->data($result['data'], meta: $result['meta'], links: $links)
            ->header('Cache-Control', RealEstateConstants::CACHE_CONTROL_HEADER)
            ->header('ETag', '"'.md5((string) json_encode($result['data'])).'"');
    }

    public function series(
        ListDppSeriesRequest $request,
        ListCountryDppSeries $listSeries,
    ): JsonResponse {
        /** @var string $code */
        $code = $request->validated('code');
        $result = $listSeries($code);

        if ($result === null) {
            return $this->response->error(
                'Not Found',
                404,
                "Country '{$code}' not found.",
            );
        }

        return $this->response->data($result['data'], meta: $result['meta'])
            ->header('Cache-Control', RealEstateConstants::CACHE_CONTROL_HEADER)
            ->header('ETag', '"'.md5((string) json_encode($result['data'])).'"');
    }

    private function buildQuery(ShowCountryDppRequest $request): DppQuery
    {
        /** @var array{offset?: int, limit?: int} $page */
        $page = $request->validated('page', []);
        /** @var string $code */
        $code = $request->validated('code');
        /** @var array<string, string> $filters */
        $filters = $request->validated('filter', []);
        /** @var string|null $sort */
        $sort = $request->validated('sort');

        return new DppQuery(
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
    private function buildPaginationLinks(DppQuery $query, int $total): array
    {
        $base = "/api/v1/real-estate/{$query->countryCode}/detailed";
        $common = array_filter(['sort' => $query->sort]);

        foreach ($query->filters as $key => $value) {
            $common["filter[{$key}]"] = $value;
        }

        $next = $query->offset + $query->limit < $total
            ? $base.'?'.http_build_query(array_merge($common, [
                'page[offset]' => $query->offset + $query->limit,
                'page[limit]' => $query->limit,
            ]))
            : null;

        $prev = $query->offset > 0
            ? $base.'?'.http_build_query(array_merge($common, [
                'page[offset]' => max(0, $query->offset - $query->limit),
                'page[limit]' => $query->limit,
            ]))
            : null;

        return ['next' => $next, 'prev' => $prev];
    }
}
