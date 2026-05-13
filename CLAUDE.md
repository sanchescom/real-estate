# Real Estate — BIS Property Prices

**Stack:** PHP 8.4, Laravel 12, MySQL 8.4, Pest v3
**Based on:** MaryPoppins reference architecture (DDD + CQS)
**Reference:** `/projects/MaryPoppins/CLAUDE.md` + `GUIDELINES.md`

## Architecture

Context-first DDD. One bounded context: `RealEstate`. Shared layer copied from MaryPoppins.

```
app/
├── RealEstate/
│   ├── App/                    # Application layer
│   │   ├── Controllers/        # Thin: validate + delegate + respond
│   │   ├── Console/            # Artisan commands (call Actions, no business logic)
│   │   ├── Requests/           # FormRequest per endpoint
│   │   ├── Providers/          # RealEstateServiceProvider (routes, bindings, schedule)
│   │   └── api.php             # Context routes
│   ├── Domain/                 # Business logic, no framework deps
│   │   ├── Commands/
│   │   │   ├── Actions/        # final readonly, CommandAction, __invoke(): void
│   │   │   └── Contracts/      # Ports (interfaces for repos, sources)
│   │   ├── Queries/
│   │   │   ├── Actions/        # final readonly, QueryAction, __invoke(): non-void
│   │   │   └── Contracts/      # Query repository interfaces
│   │   ├── Data/               # DTOs (spatie/laravel-data), final class
│   │   ├── ValueObjects/       # final readonly, constructor validation
│   │   ├── Events/             # DomainEvent marker, past tense (ImportWasCompleted)
│   │   └── Enums/              # Backed enums for dimensions
│   └── Infrastructure/         # Framework adapters
│       ├── Models/             # Eloquent models ($fillable, casts, scopes, relationships)
│       ├── Repositories/       # Implements Domain query/command contracts
│       ├── Clients/            # BIS HTTP clients (BisFileClient, BisApiClient)
│       └── Parsers/            # CSV parsers (SppCsvParser, DppCsvParser)
├── Shared/                     # Copied from MaryPoppins, adapted
│   ├── App/
│   │   ├── ApiResponse.php     # Envelope {data,meta,links}, errors, CSV streaming
│   │   ├── CsvSanitizer.php    # Formula injection protection
│   │   ├── Contracts/          # BoundedContextProvider marker
│   │   ├── Middleware/         # InnerApiKey, RequestId, LogPeakMemory
│   │   ├── Logging/           # JsonLogFormatter (structured logs)
│   │   └── HealthChecks/      # OpcacheCheck
│   ├── Domain/
│   │   └── Contracts/          # CommandAction, QueryAction, DomainEvent, EventDispatcher
│   └── Infrastructure/
│       └── LaravelEventDispatcher.php
└── Providers/
    └── AppServiceProvider.php  # Auto-discovers BoundedContextProviders
```

## CQS Rules

- **Command actions:** `final readonly`, implements `CommandAction`, `__invoke(): void`
- **Query actions:** `final readonly`, implements `QueryAction`, `__invoke()` must NOT return void
- Commands must not use QueryBuilder. Queries must not dispatch events or queue jobs
- Skip CQS for trivial CRUD: single-model, no events, no business rules
- Enforced by `tests/Architecture/CqsTest.php` via reflection

## Naming Conventions

| Entity | Convention | Example |
|--------|-----------|---------|
| Controller | Singular + `Controller` | `CountryController` |
| Model | Singular PascalCase | `SppObservation` |
| Migration | snake_case verb | `create_spp_observations_table` |
| Table | plural snake_case | `spp_observations` |
| Enum | Singular PascalCase | `ValueType::Nominal` |
| Command Action | Verb phrase | `ImportSppData` |
| Query Action | Verb phrase | `ListCountries` |
| FormRequest | `{Verb}{Model}Request` | `ListCountriesRequest` |
| Domain Event | Past tense with Was/Were | `ImportWasCompleted` |
| Artisan Command | `real-estate:{verb}` | `real-estate:import-spp` |

**Suffix bans in Domain:** Manager, Handler, Processor, Helper, Util, Service.
**Event suffix bans:** Changed, Updated, Saved, Modified.
**Enforced by:** `tests/Architecture/ArchTest.php`

## API

**Envelope** via `ApiResponse` (from MaryPoppins):
- Success: `ApiResponse::data($data, $meta, $links)` → `{"data": ..., "meta": {}, "links": {}}`
- Error: `ApiResponse::error($title, $status, $detail, $code)` → `{"errors": [...]}`
- Validation: `ApiResponse::validationErrors($errors)` → 422
- CSV: `ApiResponse::csv($rows, $filename)` → streamed CSV with CsvSanitizer

**Auth:** `X-API-Key` header via `InnerApiKeyMiddleware` (`inner-api:{realm}`). Config in `config/inner-api.php`. Multiple keys per realm for zero-downtime rotation.

**Pagination:** `page[offset]`, `page[limit]` (max 500, default 50). Response includes `links.next`, `links.prev`. Built manually with `http_build_query()`.

**Sorting:** `?sort=field` (asc), `?sort=-field` (desc).

**CSV export:** `?fmt=csv` on all data endpoints.

**Caching:** Redis TTL 24h. `Cache-Control: max-age=86400`, `ETag` headers. Cache invalidated after import.

**Routes:** `app/RealEstate/App/api.php`, loaded by `RealEstateServiceProvider` with `Route::middleware('api')->prefix('api/v1')`.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/real-estate/countries` | Countries with data availability |
| GET | `/api/v1/real-estate/{code}` | SPP price indices by country |
| GET | `/api/v1/real-estate/{code}/detailed` | DPP detailed data by country |
| GET | `/api/v1/real-estate/{code}/detailed/series` | Available DPP series for country |
| GET | `/api/v1/health` | Health check (DB, Redis, Cache, DiskSpace, Opcache) |
| GET | `/docs/api` | OpenAPI documentation (Scramble) |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `real-estate:import-spp` | Bulk import SPP from ZIP/CSV. `--dry-run` flag. |
| `real-estate:import-dpp` | Bulk import DPP from ZIP/CSV. `--dry-run` flag. |
| `real-estate:fetch-spp` | Incremental fetch SPP via SDMX API. `--country=` filter. |
| `real-estate:fetch-dpp` | Incremental fetch DPP via SDMX API. `--country=` filter. |
| `real-estate:status` | Show data status: counts, last import, next schedule, errors. |

Commands call Actions, contain no business logic. Dependencies injected in `handle()`, not constructor. Returns `self::SUCCESS`. Scheduling via `callAfterResolving(Schedule::class)` in `RealEstateServiceProvider`.

## Domain Enums

```php
// SPP dimensions
enum ValueType: string     { case Nominal = 'N'; case Real = 'R'; }
enum UnitMeasure: string   { case Index = '628'; case YearOnYear = '771'; }
enum Frequency: string     { case Quarterly = 'Q'; case Annual = 'A'; case Monthly = 'M'; case HalfYearly = 'H'; }

// DPP dimensions
enum CoveredArea: string   { case WholeCountry = '0'; case Capital = '2'; case BigCities = '4'; ... }
enum PropertyType: string  { case AllDwellings = '1'; case Houses = '2'; case Flats = '8'; ... }
enum Vintage: string       { case All = '0'; case Existing = '1'; case New = '2'; }
```

All enums have `label(): string` method via const array lookup (avoids cyclomatic complexity with many cases).

## Import Resilience

- **Idempotency:** upsert by composite key, repeated import = same result
- **Per-row try/catch:** one bad row doesn't crash the whole import
- **Row validation:** validate country, period format, value before insert
- **Chunk transactions:** `DB::transaction()` per chunk(1000), rollback per chunk not per file
- **Import report:** output imported / skipped / errors / duration to console
- **`--dry-run` flag:** parse and validate without writing to DB
- **Import mutex:** `withoutOverlapping()` — two identical imports can't run in parallel
- **Sanity check:** verify expected counts after import ("expected 61 countries, got N")
- **Domain event:** `ImportWasCompleted` dispatched with metrics (dataset, imported, skipped, errors, duration_ms)
- **Cache invalidation:** flush response cache after successful import
- **Structured logging:** `Log::info('SPP import completed', [...])` — enriched by JsonLogFormatter with hostname, request_id

## HTTP Client (BIS API)

Following MaryPoppins `DataUsaClient` pattern:
- `connectTimeout(5)->timeout(30)`
- `retry([500, 1500, 4000])` with specific status codes `[429, 502, 503]` and `ConnectionException`
- Circuit breaker via `RateLimiter`: `MAX_FAILURES=5`, `DECAY_SECONDS=60`
- `assertCircuitClosed()` before request, `RateLimiter::clear()` on success, `RateLimiter::hit()` on failure
- Scheduling: `monthlyOn(25, '03:00')`, `withoutOverlapping()`, `onOneServer()`

## Security

- **Rate limiting:** 100/min per API key (Laravel `RateLimiter::for('api', ...)`)
- **Nginx rate limiting:** `limit_req_zone` — first line of defense before PHP
- **Max page size:** 500 records — prevent bulk extraction
- **Period range validation:** reject unreasonably large ranges
- **Header hardening:** hide `X-Powered-By`, `Server`, `server_tokens off` in nginx
- **Import mutex:** prevent parallel imports via `withoutOverlapping()`
- **CSV sanitization:** `CsvSanitizer` prevents formula injection in CSV export
- **Input validation:** FormRequest on every endpoint, `->validated()` only, max length on strings, regex on periods, enum whitelist

## PHP Practices

- `declare(strict_types=1)` in every file (including route files)
- `final readonly` on all Domain classes. `final` on all Value Objects
- Constructor promotion always
- Early returns / guard clauses
- `match` over `switch`
- Method structure: Validate → Get Data → Process → Return
- Return `[]` for empty lists, `null` for missing object, throw exception for errors
- Strict comparison `===` always
- Boolean naming: `is`, `has`, `can` prefixes
- No `&$variable`, no `do...while`, no nested ternaries
- Complex conditions → extract to descriptive methods
- DocBlocks only when PHP types can't express it (typed arrays)
- `#[\Override]` attribute on overridden methods (e.g. `validationData()`)

## Laravel Practices

- `$fillable` on models (not `$guarded = []` — banned by Semgrep)
- Scopes for reusable query conditions
- `DB::transaction()` for related writes
- `chunk()` for large dataset processing (CSV import)
- No `env()` outside config files
- No `abort()` — throw exceptions
- `preventLazyLoading()` in dev (via `Model::shouldBeStrict()`)
- `CarbonImmutable` globally (set in `AppServiceProvider`)
- DI everywhere, no facades in Domain
- Events dispatch outside DB transaction (`ShouldHandleEventsAfterCommit`)
- EventDispatcher port in Domain, LaravelEventDispatcher in Infrastructure

## Testing

- Pest v3, `RefreshDatabase`, SQLite in-memory
- Tests mirror app structure: `tests/Feature/RealEstate/App/Controllers/...`
- Test Actions directly + through HTTP endpoints
- Factory states for model variations
- Mock HTTP calls to BIS API (`Http::fake()`)
- `Event::fake()` / `Queue::fake()` for side effects
- `Storage::fake('local')` in `beforeEach` where needed
- Test names as sentences: `it('imports SPP data for all countries')`
- Architecture tests: `ArchTest.php` (final, readonly, suffix bans), `CqsTest.php` (void/non-void, deps)
- Minimum 30 tests (unit + feature + architecture)

## Observability

- **Health endpoint:** `GET /api/v1/health` — DB, Redis, Cache, DiskSpace, Opcache (spatie/laravel-health)
- **Structured logs:** JsonLogFormatter adds hostname, request_id, user_id to every log entry
- **Request tracing:** RequestIdMiddleware propagates `X-Request-ID`
- **Memory monitoring:** LogPeakMemoryMiddleware warns at >80% usage
- **Status command:** `real-estate:status` — counts, last import time, next schedule, errors
- **Import events:** `ImportWasCompleted` — can wire to alerting/monitoring
- **API docs:** Scramble auto-generates OpenAPI at `/docs/api`

## Authorization

Following MaryPoppins three-scenario model:

**Current (Inner API):** Static key via `X-API-Key` header + `InnerApiKeyMiddleware`. No user model, no Sanctum. `FormRequest::authorize()` returns `true` (auth handled at middleware level).

**SaaS API readiness:** When this service becomes commercial:
- Define tiers, scopes, and rate limits per plan in this CLAUDE.md
- Add Sanctum for token-based auth with tiered permissions
- Implement `api_token` query param middleware for legacy compat (deprecated pattern — document deviation)
- Add usage metering

## Migration Safety

- Always add indexes on foreign keys and columns used in WHERE/ORDER BY
- NOT NULL: add with `->default()` or nullable first
- Expand/contract: minimum 2 deploys between add column and drop old

## Git

Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
Subject line under 70 characters, imperative mood.
Tests in same commit as feature — never separate.

## Docker

`docker compose up` must produce a working project.
Services: nginx (8080), app (PHP 8.4-FPM), mysql 8.4, redis 7.
Multi-stage Dockerfile: base, production, development.

## Forbidden

- `dd`/`dump`/`var_dump`/`die`/`exit` in committed code
- `env()` outside config
- Facades in Domain layer
- `$guarded = []`
- `$request->all()` (use `->validated()`)
- `verify => false` on HTTP clients
- `glob()` in app code (use `Storage::files()`)
- `static $` in app code (use DI)
- Raw SQL without parameterization
- Business logic in controllers, jobs, or commands
- God classes with 20+ public methods
- Abstract Factory, Builder, Mediator for single use
- Suffix Manager/Handler/Processor/Helper/Util/Service in Domain
- Event suffixes Changed/Updated/Saved/Modified
- Separate "add tests" commits — tests go with their feature
- Vague "polish" or "cleanup" commits

## Code Quality Enforcement

Installed from first commit, run before every commit:
- **PHPStan max** + ekino/phpstan-banned-code + custom rules (complexity <=10, method <=30 lines, params <=3, nesting <=2, no generic exceptions in Domain)
- **Pint** with MaryPoppins preset (strict_types, trailing commas, ordered imports)
- **Pest** — all tests must pass
- **PHPCPD** — duplication <3%
- **Compliance script** (`scripts/check-compliance.sh`) — forbidden patterns, structural rules, line length <=120

## MaryPoppins Pattern Compliance

Every new class must follow MaryPoppins pattern for its equivalent:
- **Controller** → `CubeController` pattern: `buildQuery()`, `buildPaginationLinks()` as private methods, `/** @var */` inline PHPDoc for type narrowing
- **Command** → `SyncCubesCommand` pattern: deps in `handle()`, calls Action, returns `self::SUCCESS`
- **CommandAction** → `SyncCubesFromDataUsa` pattern: `final readonly`, `__invoke(): void`, DI via constructor
- **QueryAction** → `ListCubes`/`GetCube` pattern: `final readonly`, `__invoke()` returns data
- **Repository** → `FilesystemCubeRepository` pattern: implements contract from Domain
- **ServiceProvider** → `DataCatalogServiceProvider` pattern: `loadRoutes()`, `loadCommands()`, `loadSchedule()`
- **FormRequest** → `ListCubesRequest`/`ShowCubeRequest` pattern: `#[\Override] validationData()` for route params

No utility/helper/caster classes that don't exist in MaryPoppins. No separate PaginationLinks class — build links in controller.

## Documented Deviations from MaryPoppins

**ApiResponse + CsvResponse: split and injectable.** MaryPoppins has one static ApiResponse class for both JSON and CSV. We split into ApiResponse (JSON only) and CsvResponse (CSV with inline sanitization) — Single Responsibility. Both are injectable, not static. CsvSanitizer removed — sanitize() is a private method in CsvResponse. In exception handler (bootstrap/app.php) ApiResponse resolved via `app(ApiResponse::class)` since DI is not available.

## Rule Priority

When MaryPoppins and laravel-dev rules conflict → **MaryPoppins wins** unless documented deviation exists above.
laravel-dev practices apply where MaryPoppins is silent (method structure, boolean naming, scopes, etc).
