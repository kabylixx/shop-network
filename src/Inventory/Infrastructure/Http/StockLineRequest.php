<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final class StockLineRequest
{
    #[Assert\NotBlank(message: 'The shop id is required.')]
    #[Assert\Uuid(message: 'The shop id must be a valid UUID.')]
    public ?string $shopId = null;

    #[Assert\NotNull(message: 'The quantity is required.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'The quantity must be zero or greater.')]
    public ?int $quantity = null;
}
