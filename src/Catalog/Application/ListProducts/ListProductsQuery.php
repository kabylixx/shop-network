<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListProducts;

use App\Shared\Application\Pagination;
use App\Shared\Application\SortDirection;

final readonly class ListProductsQuery
{
    public function __construct(
        public string $search,
        public string $sortField,
        public SortDirection $direction,
        public Pagination $pagination,
    ) {
    }
}
