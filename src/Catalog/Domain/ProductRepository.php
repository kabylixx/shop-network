<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ProductRepository
{
    public function save(Product $product): void;
}
