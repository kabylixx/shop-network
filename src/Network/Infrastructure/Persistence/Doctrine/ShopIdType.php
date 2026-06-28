<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Domain\ShopId;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;

/**
 * Maps {@see ShopId} to a BINARY(16) column (via symfony/uid's base type).
 */
final class ShopIdType extends AbstractUidType
{
    public const string NAME = 'shop_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getUidClass(): string
    {
        return ShopId::class;
    }
}
