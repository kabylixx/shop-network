<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

use App\Catalog\Domain\ProductId;

interface StockRepository
{
    /**
     * Existing stock lines for a product, across every shop.
     *
     * @return Stock[]
     */
    public function findAllForProduct(ProductId $productId): array;

    /**
     * Persists the given stock lines in a single transaction (all or nothing).
     *
     * @param Stock[] $stocks
     */
    public function saveAll(array $stocks): void;
}
