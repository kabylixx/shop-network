<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListProducts;

use App\Catalog\Application\ProductCatalog;
use App\Catalog\Application\ProductView;
use App\Shared\Application\Paginated;

final readonly class ListProductsQueryHandler
{
    public function __construct(private ProductCatalog $catalog)
    {
    }

    /**
     * @return Paginated<ProductView>
     */
    public function __invoke(ListProductsQuery $query): Paginated
    {
        return $this->catalog->search($query);
    }
}
