<?php

declare(strict_types=1);

namespace App\Network\Application\SearchShops;

use App\Shared\Application\Pagination;

final readonly class SearchShopsQuery
{
    public function __construct(
        public string $search,
        public ?SearchArea $area,
        public Pagination $pagination,
    ) {
    }
}
