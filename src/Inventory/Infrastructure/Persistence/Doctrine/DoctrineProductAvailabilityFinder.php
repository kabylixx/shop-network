<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Application\AvailabilityView;
use App\Inventory\Application\GetProductAvailability\GetProductAvailabilityQuery;
use App\Inventory\Application\ProductAvailabilityFinder;
use App\Network\Domain\ShopStatus;
use App\Shared\Application\Paginated;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProductAvailabilityFinder implements ProductAvailabilityFinder
{
    /**
     * Circle distance (in meters) between each shop and the search center.
     */
    private const string DISTANCE_EXPR = 'ST_Distance_Sphere(POINT(sh.longitude, sh.latitude), POINT(:lng, :lat))';

    public function __construct(private Connection $connection)
    {
    }

    public function find(GetProductAvailabilityQuery $query): Paginated
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from('stock', 's')
            ->innerJoin('s', 'shop', 'sh', 'sh.id = s.shop_id')
            ->where('s.product_id = :productId')
            ->setParameter('productId', $query->productId->toBinary())
            ->andWhere('s.quantity > 0')
            ->andWhere('sh.status = :status')
            ->setParameter('status', ShopStatus::Open->value);

        $area = $query->area;
        if (null !== $area) {
            $queryBuilder->andWhere(self::DISTANCE_EXPR.' <= :radius')
                ->setParameter('lng', $area->center->longitude)
                ->setParameter('lat', $area->center->latitude)
                ->setParameter('radius', $area->radiusInMeters);
        }

        $total = (int) (clone $queryBuilder)
            ->select('COUNT(*)')
            ->executeQuery()
            ->fetchOne();

        $data = (clone $queryBuilder)
            ->select('sh.id', 'sh.name', 'sh.address', 'sh.latitude', 'sh.longitude', 'sh.status', 's.quantity')
            ->setFirstResult($query->pagination->offset())
            ->setMaxResults($query->pagination->limit);

        if (null !== $area) {
            $data->addSelect(self::DISTANCE_EXPR.' AS distance')
                ->orderBy('distance', 'ASC')
                ->addOrderBy('sh.id', 'ASC');
        } else {
            $data->orderBy('sh.name', 'ASC')
                ->addOrderBy('sh.id', 'ASC');
        }

        $items = array_map(
            static fn (array $row): AvailabilityView => AvailabilityView::fromRow(
                (string) Uuid::fromBinary((string) $row['id']),
                (string) $row['name'],
                (string) $row['address'],
                (float) $row['latitude'],
                (float) $row['longitude'],
                (string) $row['status'],
                (int) $row['quantity'],
                isset($row['distance']) ? (float) $row['distance'] : null,
            ),
            $data->executeQuery()->fetchAllAssociative(),
        );

        return Paginated::fromPagination($items, $total, $query->pagination);
    }
}
