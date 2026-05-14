<?php

declare(strict_types=1);

namespace App\Shared\App;

final readonly class PaginationContext
{
    /** @param  array<string, mixed>  $params */
    public function __construct(
        private string $base,
        private int $offset,
        private int $limit,
        private int $total,
        private array $params = [],
    ) {}

    public function hasNext(): bool
    {
        return $this->offset + $this->limit < $this->total;
    }

    public function hasPrev(): bool
    {
        return $this->offset > 0;
    }

    public function nextUrl(): string
    {
        return $this->buildUrl($this->offset + $this->limit);
    }

    public function prevUrl(): string
    {
        return $this->buildUrl(max(0, $this->offset - $this->limit));
    }

    private function buildUrl(int $offset): string
    {
        return $this->base.'?'.http_build_query(array_merge($this->params, [
            'page[offset]' => $offset,
            'page[limit]' => $this->limit,
        ]));
    }
}
