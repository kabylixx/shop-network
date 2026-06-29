<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Application\GetStockByShops\GetStockByShopsQuery;
use App\Inventory\Application\StockFinder;
use App\Inventory\Application\StockView;
use App\Network\Domain\ShopId;
use App\Shared\Application\Paginated;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineStockFinder implements StockFinder
{
    public function __construct(private Connection $connection)
    {
    }

    public function findByShops(GetStockByShopsQuery $query): Paginated
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from('stock', 's')
            ->innerJoin('s', 'product', 'p', 'p.id = s.product_id');

        if ([] !== $query->shopIds) {
            $queryBuilder->andWhere('s.shop_id IN (:shopIds)')
                ->setParameter(
                    'shopIds',
                    array_map(static fn (ShopId $id): string => $id->toBinary(), $query->shopIds),
                    ArrayParameterType::BINARY,
                );
        }

        if (!$query->includeOutOfStock) {
            $queryBuilder->andWhere('s.quantity > 0');
        }

        $total = (int) (clone $queryBuilder)
            ->select('COUNT(*)')
            ->executeQuery()
            ->fetchOne();

        $rows = (clone $queryBuilder)
            ->select('s.product_id', 'p.name', 'p.picture_url', 's.shop_id', 's.quantity')
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('s.shop_id', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->setFirstResult($query->pagination->offset())
            ->setMaxResults($query->pagination->limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(
            static fn (array $row): StockView => StockView::fromRow(
                (string) Uuid::fromBinary((string) $row['product_id']),
                (string) $row['name'],
                (string) $row['picture_url'],
                (string) Uuid::fromBinary((string) $row['shop_id']),
                (int) $row['quantity'],
            ),
            $rows,
        );

        return Paginated::fromPagination($items, $total, $query->pagination);
    }
}
