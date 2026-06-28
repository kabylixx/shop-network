<?php

declare(strict_types=1);

namespace App\Network\Domain;

use Symfony\Component\Uid\Uuid;

final class ShopId extends Uuid
{
    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }
}
