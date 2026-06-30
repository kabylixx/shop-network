<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\ProductId;
use App\Inventory\Domain\Quantity;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockId;
use App\Inventory\Domain\StockRepository;
use App\Network\Domain\ShopId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineStockRepositoryTest extends KernelTestCase
{
    private StockRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(StockRepository::class);
    }

    public function testItInsertsANewCouple(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $shopId = ShopId::generate();

        // Act
        $this->repository->upsertAll([
            Stock::create(StockId::generate(), $productId, $shopId, new Quantity(5)),
        ]);

        // Assert
        self::assertSame([(string) $shopId => 5], $this->quantitiesFor($productId));
    }

    public function testItReplacesAnExistingCoupleWithoutDuplicating(): void
    {
        // Arrange — the same couple is written twice, as two racing requests would
        $productId = ProductId::generate();
        $shopId = ShopId::generate();

        // Act
        $this->repository->upsertAll([
            Stock::create(StockId::generate(), $productId, $shopId, new Quantity(5)),
        ]);
        $this->repository->upsertAll([
            Stock::create(StockId::generate(), $productId, $shopId, new Quantity(8)),
        ]);

        // Assert — a single row survives with the latest quantity, no unique-constraint failure
        self::assertSame([(string) $shopId => 8], $this->quantitiesFor($productId));
    }

    /**
     * @return array<string, int> quantity indexed by shop id
     */
    private function quantitiesFor(ProductId $productId): array
    {
        // Read straight from the DB (not through the repository under test) to
        // assert the upsert's effect on the actual table.
        $rows = self::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->fetchAllAssociative('SELECT shop_id, quantity FROM stock WHERE product_id = ?', [$productId->toBinary()]);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) Uuid::fromBinary((string) $row['shop_id'])] = (int) $row['quantity'];
        }

        return $map;
    }
}
