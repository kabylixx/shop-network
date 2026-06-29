<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Application\ShopExistence;
use App\Network\Domain\ShopId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Resolves shop existence in a single batch query against the Network `shop`
 * table. Same rationale as {@see DbalProductExistence}: schema-level coupling
 * only, no dependency on a Network class.
 */
final readonly class DbalShopExistence implements ShopExistence
{
    public function __construct(private Connection $connection)
    {
    }

    public function existing(array $shopIds): array
    {
        if ([] === $shopIds) {
            return [];
        }

        $present = $this->connection->executeQuery(
            'SELECT id FROM shop WHERE id IN (:ids)',
            ['ids' => array_map(static fn (ShopId $id): string => $id->toBinary(), $shopIds)],
            ['ids' => ArrayParameterType::BINARY],
        )->fetchFirstColumn();

        $presentHex = array_map(static fn (string $binary): string => bin2hex($binary), $present);

        return array_values(array_filter(
            $shopIds,
            static fn (ShopId $id): bool => \in_array(bin2hex($id->toBinary()), $presentHex, true),
        ));
    }
}
