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
     * Atomically upserts the given stock lines by their (shop, product) couple,
     * in a single statement: an existing couple has its quantity replaced, a new
     * one is inserted. Concurrency-safe by construction — it leans on the
     * uniq_stock_shop_product unique index rather than a read-modify-write, so
     * two requests racing on the same couple can neither duplicate a row nor
     * fail on the unique constraint.
     *
     * @param Stock[] $stocks
     */
    public function upsertAll(array $stocks): void;
}
