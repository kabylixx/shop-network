<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\ProductId;
use App\Inventory\Application\ProductExistence;
use Doctrine\DBAL\Connection;

/**
 * Resolves product existence by querying the Catalog `product` table directly.
 *
 * Inventory depends on no Catalog class here: the coupling is reduced to the
 * shared schema (the table name), isolated in this adapter — consistent with a
 * single bounded context whose modules share one database.
 */
final readonly class DbalProductExistence implements ProductExistence
{
    public function __construct(private Connection $connection)
    {
    }

    public function exists(ProductId $productId): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM product WHERE id = :id',
            ['id' => $productId->toBinary()],
        );

        return (int) $count > 0;
    }
}
