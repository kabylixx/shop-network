<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetStockByShops;

use App\Network\Domain\ShopId;
use App\Shared\Application\Pagination;

final readonly class GetStockByShopsQuery
{
    /**
     * @param ShopId[] $shopIds filter by these shops; empty means every shop
     */
    public function __construct(
        public array $shopIds,
        public bool $includeOutOfStock,
        public Pagination $pagination,
    ) {
    }
}
