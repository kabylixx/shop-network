<?php

declare(strict_types=1);

namespace App\Inventory\Application\SetStock;

final readonly class StockLine
{
    public function __construct(
        public string $shopId,
        public int $quantity,
    ) {
    }
}
