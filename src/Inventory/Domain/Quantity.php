<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(\sprintf('The quantity must be zero or greater, got %d.', $value));
        }
    }
}
