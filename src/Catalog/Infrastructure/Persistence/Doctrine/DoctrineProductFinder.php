<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Application\ListProducts\ListProductsQuery;
use App\Catalog\Application\ProductFinder;
use App\Catalog\Application\ProductView;
use App\Catalog\Domain\Product;
use App\Shared\Application\Paginated;
use App\Shared\Application\SortDirection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProductFinder implements ProductFinder
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function search(ListProductsQuery $query): Paginated
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Product::class, 'p');

        if ('' !== $query->search) {
            $queryBuilder->andWhere('p.name LIKE :search ESCAPE \'\\\'')
                ->setParameter('search', '%'.$this->escapeLike($query->search).'%');
        }

        $total = (int) (clone $queryBuilder)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $direction = SortDirection::Desc === $query->direction ? 'DESC' : 'ASC';

        $products = $queryBuilder
            ->select('p.id', 'p.name', 'p.pictureUrl')
            ->orderBy($this->sortColumn($query->sortField), $direction)
            ->addOrderBy('p.id', 'ASC')
            ->setFirstResult($query->pagination->offset())
            ->setMaxResults($query->pagination->limit)
            ->getQuery()
            ->getArrayResult();

        $items = array_values(array_map(
            static fn (array $row): ProductView => ProductView::fromRow(
                (string) $row['id'],
                $row['name'],
                $row['pictureUrl'],
            ),
            $products,
        ));

        return Paginated::fromPagination($items, $total, $query->pagination);
    }

    /**
     * Allow-list mapping: a sort field that isn't whitelisted never reaches SQL.
     */
    private function sortColumn(string $field): string
    {
        return match ($field) {
            'name' => 'p.name',
            default => throw new \InvalidArgumentException(\sprintf('Unsupported sort field "%s".', $field)),
        };
    }

    /**
     * Escapes the LIKE wildcards so a search for "50%" matches it literally
     * instead of treating it as a wildcard pattern.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
