<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final class ShopProductsRequest
{
    #[Assert\Positive(message: 'The page must be a positive integer.')]
    public int $page = 1;

    #[Assert\Range(notInRangeMessage: 'The limit must be between {{ min }} and {{ max }}.', min: 1, max: 100)]
    public int $limit = 20;

    public bool $includeOutOfStock = false;
}
