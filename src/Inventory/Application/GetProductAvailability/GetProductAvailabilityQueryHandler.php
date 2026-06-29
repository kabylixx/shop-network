<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetProductAvailability;

use App\Inventory\Application\AvailabilityView;
use App\Inventory\Application\ProductAvailabilityFinder;
use App\Inventory\Application\ProductExistence;
use App\Inventory\Domain\ProductNotFoundException;
use App\Shared\Application\Paginated;

final readonly class GetProductAvailabilityQueryHandler
{
    public function __construct(
        private ProductAvailabilityFinder $finder,
        private ProductExistence $productExistence,
    ) {
    }

    /**
     * Lists the open shops that stock the product (quantity > 0), optionally
     * restricted to a geographic disk and sorted nearest-first. The product is
     * an addressed resource: an unknown product yields a 404 (not an empty list).
     *
     * @return Paginated<AvailabilityView>
     */
    public function __invoke(GetProductAvailabilityQuery $query): Paginated
    {
        if (!$this->productExistence->exists($query->productId)) {
            throw ProductNotFoundException::withId($query->productId);
        }

        return $this->finder->find($query);
    }
}
