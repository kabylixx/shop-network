<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetShopProducts;

use App\Inventory\Application\GetStockByShops\GetStockByShopsQuery;
use App\Inventory\Application\ShopExistence;
use App\Inventory\Application\StockFinder;
use App\Inventory\Application\StockView;
use App\Inventory\Domain\ShopNotFoundException;
use App\Shared\Application\Paginated;

final readonly class GetShopProductsQueryHandler
{
    public function __construct(
        private StockFinder $stockFinder,
        private ShopExistence $shopExistence,
    ) {
    }

    /**
     * Unlike the tolerant /api/stock filter, this resource-scoped use case
     * requires the shop to exist: an unknown shop yields a 404.
     *
     * @return Paginated<StockView>
     */
    public function __invoke(GetShopProductsQuery $query): Paginated
    {
        if ([] === $this->shopExistence->existing([$query->shopId])) {
            throw ShopNotFoundException::withIds([$query->shopId]);
        }

        return $this->stockFinder->findByShops(new GetStockByShopsQuery(
            shopIds: [$query->shopId],
            includeOutOfStock: $query->includeOutOfStock,
            pagination: $query->pagination,
        ));
    }
}
