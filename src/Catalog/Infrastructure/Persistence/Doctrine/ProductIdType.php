<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\ProductId;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;

/**
 * Maps {@see ProductId} to a BINARY(16) column (via symfony/uid's base type).
 */
final class ProductIdType extends AbstractUidType
{
    public const string NAME = 'product_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getUidClass(): string
    {
        return ProductId::class;
    }
}
