<?php

declare(strict_types=1);

namespace App\Inventory\Application\SetStock;

final readonly class SetStockCommand
{
    /**
     * @param StockLine[] $lines the (shop, quantity) couples to upsert for the product
     */
    public function __construct(
        public string $productId,
        public array $lines,
    ) {
    }
}
