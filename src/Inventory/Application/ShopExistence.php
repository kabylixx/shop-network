<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Network\Domain\ShopId;

/**
 * Inventory-owned port: resolves, in a single batch, which of the given shops
 * exist. The handler derives the missing ones to raise a 404.
 */
interface ShopExistence
{
    /**
     * @param ShopId[] $shopIds
     *
     * @return ShopId[] the subset of $shopIds that exists
     */
    public function existing(array $shopIds): array;
}
