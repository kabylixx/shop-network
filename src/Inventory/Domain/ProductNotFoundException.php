<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

use App\Catalog\Domain\ProductId;
use App\Shared\Domain\NotFoundException;

final class ProductNotFoundException extends NotFoundException
{
    public static function withId(ProductId $id): self
    {
        return new self(\sprintf('Product "%s" does not exist.', $id));
    }
}
