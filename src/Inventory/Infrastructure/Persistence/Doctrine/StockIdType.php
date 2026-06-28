<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\StockId;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;

final class StockIdType extends AbstractUidType
{
    public const string NAME = 'stock_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getUidClass(): string
    {
        return StockId::class;
    }
}
