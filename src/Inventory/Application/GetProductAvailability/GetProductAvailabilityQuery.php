<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetProductAvailability;

use App\Catalog\Domain\ProductId;
use App\Shared\Application\Pagination;
use App\Shared\Domain\SearchArea;

final readonly class GetProductAvailabilityQuery
{
    public function __construct(
        public ProductId $productId,
        public ?SearchArea $area,
        public Pagination $pagination,
    ) {
    }
}
