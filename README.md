# BIS Real Estate Data

REST API service for BIS (Bank for International Settlements) Residential Property Prices. Imports SPP and DPP datasets, serves via JSON API with filtering, pagination, sorting, and CSV export.

## Postman Collection

Import `docs/postman-collection.json` into Postman. Set variable `baseUrl` to your host (default `http://localhost:8081`). 22 pre-configured requests covering all endpoints, filters, CSV export, pagination, and error scenarios.

## Quick Start

```bash
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# Import data
docker compose exec app php artisan real-estate:import-spp
docker compose exec app php artisan real-estate:import-dpp

# Verify
docker compose exec app php artisan real-estate:status
```

API available at `http://localhost:8081`

## Authentication

All `/api/v1/real-estate/*` endpoints require `X-API-Key` header.

```bash
curl -H "X-API-Key: your-key" http://localhost:8081/api/v1/real-estate/countries
```

Keys configured via `INNER_API_KEYS_REAL_ESTATE` env variable (comma-separated for rotation).

## Endpoints

### List Countries

```
GET /api/v1/real-estate/countries
```

```bash
curl -H "X-API-Key: key" \
  "http://localhost:8081/api/v1/real-estate/countries?sort=name&page[limit]=10"
```

```json
{
  "data": [
    {"code": "US", "name": "United States", "has_spp": true, "has_dpp": true}
  ],
  "meta": {"total": 64, "offset": 0, "limit": 10},
  "links": {"next": "...?page[offset]=10&page[limit]=10", "prev": null}
}
```

### SPP Data (Selected Property Prices)

```
GET /api/v1/real-estate/{code}
```

Filters: `filter[type]` (nominal, real), `filter[metric]` (index, yoy), `filter[from]` (YYYY-QN), `filter[to]` (YYYY-QN)

```bash
curl -H "X-API-Key: key" \
  "http://localhost:8081/api/v1/real-estate/US?filter[type]=nominal&filter[metric]=index&filter[from]=2020-Q1&filter[to]=2020-Q4"
```

```json
{
  "data": [
    {"period": "2020-Q1", "value": 158.4799},
    {"period": "2020-Q2", "value": 159.8064},
    {"period": "2020-Q3", "value": 164.9151},
    {"period": "2020-Q4", "value": 171.0508}
  ],
  "meta": {
    "country_code": "US",
    "type": "nominal",
    "metric": "index",
    "base_year": "2010 = 100",
    "frequency": "quarterly",
    "source": "BIS",
    "total": 4
  }
}
```

### DPP Data (Detailed Property Prices)

```
GET /api/v1/real-estate/{code}/detailed
```

Filters: `filter[area]`, `filter[property_type]`, `filter[vintage]`, `filter[freq]` (Q, A, M, H), `filter[from]`, `filter[to]`

```bash
curl -H "X-API-Key: key" \
  "http://localhost:8081/api/v1/real-estate/AU/detailed?filter[area]=0&filter[property_type]=1&page[limit]=3"
```

### DPP Available Series

```
GET /api/v1/real-estate/{code}/detailed/series
```

Returns available DPP series with dimensions and unit of measure.

```bash
curl -H "X-API-Key: key" \
  "http://localhost:8081/api/v1/real-estate/AU/detailed/series"
```

### Health & Version

```
GET /api/health       # DB, Redis, Cache, Disk, Opcache checks
GET /api/version      # App version, Laravel, PHP
```

## Query Parameters

All data endpoints support:

| Parameter | Description | Example |
|-----------|-------------|---------|
| `page[offset]` | Skip N records | `page[offset]=50` |
| `page[limit]` | Records per page (max 500) | `page[limit]=25` |
| `sort` | Sort field, prefix `-` for desc | `sort=-period` |
| `fmt` | Response format | `fmt=csv` |
| `filter[...]` | Filter by field | `filter[type]=nominal` |

## CSV Export

Add `?fmt=csv` to any data endpoint. CSV includes UTF-8 BOM and formula injection protection.

```bash
curl -H "X-API-Key: key" \
  "http://localhost:8081/api/v1/real-estate/US?fmt=csv&filter[type]=nominal&filter[metric]=index"
```

## Caching

Two layers of caching:

**Server-side (spatie/laravel-responsecache):**
- All GET responses cached in Redis
- Different query parameters = different cache keys
- Cache automatically invalidated after import (`ResponseCache::clear()`)
- 5-7x faster responses on cache hit

**Client-side (HTTP headers):**
- `Cache-Control: max-age=86400` on all data endpoints
- `ETag` header — different per data set, consistent across identical requests
- `If-None-Match` support — returns `304 Not Modified` when data unchanged (via ETagMiddleware)

## Artisan Commands

| Command | Description |
|---------|-------------|
| `real-estate:import-spp` | Bulk import SPP from BIS ZIP/CSV |
| `real-estate:import-dpp` | Bulk import DPP from BIS ZIP/CSV |
| `real-estate:fetch-spp` | Incremental fetch via SDMX API |
| `real-estate:fetch-dpp` | Incremental fetch via SDMX API |
| `real-estate:status` | Show data counts and last import |

Options: `--dry-run` (import), `--country=XX` (fetch), `--isolated` (mutex)

## Scheduling

Incremental fetch runs automatically on the 25th of each month:
- `real-estate:fetch-spp` at 03:00
- `real-estate:fetch-dpp` at 04:00

## Observability

- **Request tracing:** `X-Request-ID` header propagated through every request (auto-generated ULID if not provided)
- **Structured logging:** JSON log format with hostname, request_id, user_id in every log entry (JsonLogFormatter)
- **Memory monitoring:** LogPeakMemoryMiddleware warns when peak memory exceeds 80% of limit
- **Status command:** `real-estate:status` shows current data counts, last import timestamps, next scheduled fetch
- **Import events:** `ImportWasCompleted` domain event dispatched with metrics — can wire to alerting
- **Health checks:** `GET /api/health` — Database, Redis, Cache, DiskSpace, Opcache

## Pagination

All list endpoints support offset-based pagination:

- `page[offset]` — skip N records (default: 0)
- `page[limit]` — records per page (default: 50, max: 500)
- Response includes `links.next` and `links.prev` with all current filters preserved
- ETag header is consistent across identical requests for cache validation

## Security

- API key authentication with multi-key rotation
- Constant-time key comparison (`hash_equals`) — prevents timing attacks
- Rate limiting: 100 req/min per key
- Nginx rate limiting (`limit_req_zone`)
- Max page size: 500 records
- Period format validation, enum whitelists
- CSV formula injection protection
- Zip-slip protection on bulk file extraction
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`
- No server version disclosure
- `JSON_THROW_ON_ERROR` on all json_encode calls

## Import Resilience

- Idempotent: repeated import produces same result
- Per-row try/catch in parsers — one bad CSV row doesn't crash import
- Per-row validation before insert
- Chunk transactions: failed chunk doesn't affect others
- `--dry-run`: validate without writing
- `--isolated`: prevents concurrent imports
- Circuit breaker on BIS API (5 failures, 60s cooldown)
- Retry with backoff: [500, 1500, 4000]ms
- Import event dispatched for monitoring

## Architecture

DDD with CQS, based on MaryPoppins reference architecture.

```
app/
├── RealEstate/              # Bounded context
│   ├── App/                 # Controllers, Console, Requests, Routes
│   ├── Domain/              # Commands, Queries, Data, Enums, Events
│   └── Infrastructure/      # Models, Repositories, Clients, Parsers
├── Shared/                  # ApiResponse, CsvResponse, Middleware
└── Providers/               # AppServiceProvider (auto-discovery)
```

Key patterns:
- Command actions: `__invoke(): void`, DI only, no facades in Domain
- Query actions: `__invoke()` returns data, single DTO parameter
- Controllers follow CubeController pattern: `buildQuery()` + `buildPaginationLinks()`
- Contracts in Domain, implementations in Infrastructure
- `filter[]` query parameter pattern for API filtering

## Code Quality

- PHPStan max level + custom rules (complexity, method length, params, nesting)
- ekino/phpstan-banned-code
- Laravel Pint with strict formatting
- PHPCPD for duplication detection
- Architecture tests (ArchTest + CqsTest)
- Compliance script: 35+ automated checks

## Improvements over MaryPoppins

Documented deviations where we improve on the reference architecture:

| What | MaryPoppins | Our improvement |
|------|-------------|-----------------|
| ApiResponse | Static methods | Injectable instances (SRP, testable, mockable) |
| CsvResponse | Part of ApiResponse | Separate class (Single Responsibility) |
| CsvSanitizer | Separate static class | Inlined as private method in CsvResponse |
| API key comparison | `in_array()` | `hash_equals()` — constant-time, prevents timing attacks |
| Pagination links | Only `next` | Both `next` and `prev` |
| Caching | No caching headers | `Cache-Control` + `ETag` on all data endpoints |
| Pagination links | Hardcoded URLs | `route()` helper — full URLs, no hardcoded paths |
| Domain purity | `now()` / `CarbonImmutable::now()` in Domain | Timestamps in Infrastructure, `\DateTimeImmutable` injected |
| Events | `CarbonImmutable` in Domain event | `\DateTimeImmutable` — no Carbon dependency in Domain |
| Zip extraction | No zip-slip protection | Path validation after extraction |
| CSV parsers | No per-row error handling | try/catch per row — one bad row doesn't crash import |
| PHP typed constants | Not used (PHP 8.2) | PHP 8.4 typed constants (`const string`, `const int`) |
| JSON encoding | No error handling | `JSON_THROW_ON_ERROR` on all json_encode calls |
| Response cache | No server-side caching | spatie/laravel-responsecache in Redis, 5-7x speedup |
| ETag | No conditional requests | Full ETag with `If-None-Match` → 304 Not Modified |

## Stack

PHP 8.4, Laravel 12, MySQL 8.4, Redis 7, Pest, Docker
