<?php

declare(strict_types=1);

namespace App\Shared\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequestIdMiddleware
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?: (string) Str::ulid();

        $request->headers->set('X-Request-ID', $requestId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
