<?php

declare(strict_types=1);

namespace App\Catalog\Application\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public string $pictureUrl,
    ) {
    }
}
