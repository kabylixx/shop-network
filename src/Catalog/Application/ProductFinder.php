<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Application\ListProducts\ListProductsQuery;
use App\Shared\Application\Paginated;

/**
 * Read port for the product catalogue. Returns view models directly, without
 * loading the Product aggregate — the write side keeps its own ProductRepository.
 */
interface ProductFinder
{
    /**
     * @return Paginated<ProductView>
     */
    public function search(ListProductsQuery $query): Paginated;
}
