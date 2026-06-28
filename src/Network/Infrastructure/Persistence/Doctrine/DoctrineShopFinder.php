<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Application\SearchShops\SearchShopsQuery;
use App\Network\Application\ShopFinder;
use App\Network\Application\ShopView;
use App\Network\Domain\ShopStatus;
use App\Shared\Application\Paginated;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineShopFinder implements ShopFinder
{
    /**
     * Circle distance (in meters) between each shop and the search center.
     */
    private const string DISTANCE_EXPR = 'ST_Distance_Sphere(POINT(s.longitude, s.latitude), POINT(:lng, :lat))';

    public function __construct(private Connection $connection)
    {
    }

    public function search(SearchShopsQuery $query): Paginated
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from('shop', 's')
            ->where('s.status = :status')
            ->setParameter('status', ShopStatus::Open->value);

        if ('' !== $query->search) {
            $queryBuilder->andWhere("s.name LIKE :search ESCAPE '\\\\'")
                ->setParameter('search', '%'.$this->escapeLike($query->search).'%');
        }

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
            ->select('s.id', 's.name', 's.address', 's.latitude', 's.longitude', 's.manager_id', 's.status')
            ->setFirstResult($query->pagination->offset())
            ->setMaxResults($query->pagination->limit);

        if (null !== $area) {
            $data->addSelect(self::DISTANCE_EXPR.' AS distance')
                ->orderBy('distance', 'ASC')
                ->addOrderBy('s.id', 'ASC');
        } else {
            $data->orderBy('s.name', 'ASC')
                ->addOrderBy('s.id', 'ASC');
        }

        $items = array_map(
            static fn (array $row): ShopView => ShopView::fromRow(
                (string) Uuid::fromBinary((string) $row['id']),
                (string) $row['name'],
                (string) $row['address'],
                (float) $row['latitude'],
                (float) $row['longitude'],
                (string) Uuid::fromBinary((string) $row['manager_id']),
                (string) $row['status'],
                isset($row['distance']) ? (float) $row['distance'] : null,
            ),
            $data->executeQuery()->fetchAllAssociative(),
        );

        return Paginated::fromPagination($items, $total, $query->pagination);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
