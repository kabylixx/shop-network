<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

use Symfony\Component\Uid\Uuid;

final class StockId extends Uuid
{
    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }
}
