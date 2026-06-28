<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Catalog\Domain\ProductId;

/**
 * Inventory-owned port: answers whether a product exists, without depending on
 * the Catalog module's write repository. The adapter lives in Inventory's
 * Infrastructure layer.
 */
interface ProductExistence
{
    public function exists(ProductId $productId): bool;
}
