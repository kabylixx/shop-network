<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Product;

final readonly class ProductView
{
    private function __construct(
        public string $id,
        public string $name,
        public string $pictureUrl,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            (string) $product->id(),
            $product->name(),
            $product->pictureUrl(),
        );
    }

    public static function fromRow(string $id, string $name, string $pictureUrl): self
    {
        return new self($id, $name, $pictureUrl);
    }
}
