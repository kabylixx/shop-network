<?php

declare(strict_types=1);

namespace App\Catalog\Application\CreateProduct;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductRepository;

final readonly class CreateProductCommandHandler
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function __invoke(CreateProductCommand $command): Product
    {
        $product = Product::create(ProductId::generate(), $command->name, $command->pictureUrl);

        $this->products->save($product);

        return $product;
    }
}
