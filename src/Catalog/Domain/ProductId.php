<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use Symfony\Component\Uid\Uuid;

final class ProductId extends Uuid
{
    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }
}
