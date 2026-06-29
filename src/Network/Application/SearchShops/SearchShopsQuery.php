<?php

declare(strict_types=1);

namespace App\Network\Application\SearchShops;

use App\Shared\Application\Pagination;
use App\Shared\Domain\SearchArea;

final readonly class SearchShopsQuery
{
    public function __construct(
        public string $search,
        public ?SearchArea $area,
        public Pagination $pagination,
    ) {
    }
}
