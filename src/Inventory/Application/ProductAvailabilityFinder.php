<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Inventory\Application\GetProductAvailability\GetProductAvailabilityQuery;
use App\Shared\Application\Paginated;

interface ProductAvailabilityFinder
{
    /**
     * @return Paginated<AvailabilityView>
     */
    public function find(GetProductAvailabilityQuery $query): Paginated;
}
