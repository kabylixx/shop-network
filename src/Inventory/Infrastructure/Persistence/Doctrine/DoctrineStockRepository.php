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

    public function upsertAll(array $stocks): void
    {
        if ([] === $stocks) {
            return;
        }

        $placeholders = [];
        $parameters = [];
        foreach ($stocks as $stock) {
            $placeholders[] = '(?, ?, ?, ?)';
            $parameters[] = $stock->id()->toBinary();
            $parameters[] = $stock->productId()->toBinary();
            $parameters[] = $stock->shopId()->toBinary();
            $parameters[] = $stock->quantity()->value;
        }

        // One atomic statement: a new (shop, product) couple is inserted, an
        // existing one has its quantity replaced. The uniq_stock_shop_product
        // index makes this race-safe — no explicit lock, no read-modify-write.
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO stock (id, product_id, shop_id, quantity) VALUES '
            .implode(', ', $placeholders)
            .' AS new ON DUPLICATE KEY UPDATE quantity = new.quantity',
            $parameters,
        );
    }
}
