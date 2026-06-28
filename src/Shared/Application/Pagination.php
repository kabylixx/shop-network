<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * Pagination request: a 1-based page number and a page size.
 */
final readonly class Pagination
{
    public function __construct(
        public int $page,
        public int $limit,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
