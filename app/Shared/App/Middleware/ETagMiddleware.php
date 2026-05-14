<?php

declare(strict_types=1);

namespace App\Shared\App\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ETagMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $etag = $response->headers->get('ETag');

        if ($etag === null) {
            return $response;
        }

        $ifNoneMatch = $request->header('If-None-Match');

        if ($ifNoneMatch === $etag) {
            return response()->noContent(304)->header('ETag', $etag);
        }

        return $response;
    }
}
