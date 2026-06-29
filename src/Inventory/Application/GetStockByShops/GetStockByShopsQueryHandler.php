<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetStockByShops;

use App\Inventory\Application\StockFinder;
use App\Inventory\Application\StockView;
use App\Shared\Application\Paginated;

final readonly class GetStockByShopsQueryHandler
{
    public function __construct(private StockFinder $stockFinder)
    {
    }

    /**
     * @return Paginated<StockView>
     */
    public function __invoke(GetStockByShopsQuery $query): Paginated
    {
        return $this->stockFinder->findByShops($query);
    }
}
