<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetShopProducts;

use App\Network\Domain\ShopId;
use App\Shared\Application\Pagination;

final readonly class GetShopProductsQuery
{
    public function __construct(
        public ShopId $shopId,
        public bool $includeOutOfStock,
        public Pagination $pagination,
    ) {
    }
}
