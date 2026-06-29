<?php

declare(strict_types=1);

namespace App\Inventory\Application;

final readonly class StockView
{
    private function __construct(
        public string $productId,
        public string $productName,
        public string $pictureUrl,
        public string $shopId,
        public int $quantity,
    ) {
    }

    public static function fromRow(
        string $productId,
        string $productName,
        string $pictureUrl,
        string $shopId,
        int $quantity,
    ): self {
        return new self($productId, $productName, $pictureUrl, $shopId, $quantity);
    }
}
