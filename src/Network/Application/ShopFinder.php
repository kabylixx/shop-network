<?php

declare(strict_types=1);

namespace App\Network\Application;

use App\Network\Application\SearchShops\SearchShopsQuery;
use App\Shared\Application\Paginated;

interface ShopFinder
{
    /**
     * @return Paginated<ShopView>
     */
    public function search(SearchShopsQuery $query): Paginated;
}
