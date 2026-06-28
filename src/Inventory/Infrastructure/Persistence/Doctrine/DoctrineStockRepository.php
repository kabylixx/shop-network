<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\ProductId;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStockRepository implements StockRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAllForProduct(ProductId $productId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Stock::class, 's')
            ->where('s.productId = :productId')
            ->setParameter('productId', $productId, 'product_id')
            ->getQuery()
            ->getResult();
    }

    public function saveAll(array $stocks): void
    {
        foreach ($stocks as $stock) {
            $this->entityManager->persist($stock);
        }

        $this->entityManager->flush();
    }
}
