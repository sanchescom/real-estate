<?php

declare(strict_types=1);

namespace App\Shared\App;

final readonly class Pagination
{
    /**
     * Build next/prev pagination links.
     *
     * @return array{next: ?string, prev: ?string}
     */
    public function links(PaginationContext $ctx): array
    {
        return [
            'next' => $ctx->hasNext() ? $ctx->nextUrl() : null,
            'prev' => $ctx->hasPrev() ? $ctx->prevUrl() : null,
        ];
    }
}
