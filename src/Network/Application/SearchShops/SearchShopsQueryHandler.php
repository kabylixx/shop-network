<?php

declare(strict_types=1);

namespace App\Network\Application\SearchShops;

use App\Network\Application\ShopFinder;
use App\Network\Application\ShopView;
use App\Shared\Application\Paginated;

final readonly class SearchShopsQueryHandler
{
    public function __construct(private ShopFinder $finder)
    {
    }

    /**
     * @return Paginated<ShopView>
     */
    public function __invoke(SearchShopsQuery $query): Paginated
    {
        return $this->finder->search($query);
    }
}
