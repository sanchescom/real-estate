<?php

declare(strict_types=1);

namespace App\Shared\App\Middleware;

use App\Shared\App\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class InnerApiKeyMiddleware
{
    public function __construct(
        private ApiResponse $response,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $realm): Response
    {
        $keys = config("inner-api.realms.{$realm}.keys");

        if (! is_array($keys) || $keys === []) {
            return $this->response->error('Forbidden', 403, "Realm '{$realm}' is not configured.");
        }

        $provided = $request->header('X-API-Key');

        if ($provided === null || ! in_array($provided, $keys, true)) {
            return $this->response->error('Forbidden', 403, 'Invalid or missing API key.');
        }

        return $next($request);
    }
}
