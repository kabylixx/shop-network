<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Inventory\Application\GetStockByShops\GetStockByShopsQuery;
use App\Shared\Application\Paginated;

interface StockFinder
{
    /**
     * @return Paginated<StockView>
     */
    public function findByShops(GetStockByShopsQuery $query): Paginated;
}
