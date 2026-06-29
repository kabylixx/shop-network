<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Inventory\Domain\Quantity;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockId;
use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use App\Network\Domain\Shop;
use App\Network\Domain\ShopId;
use App\Network\Domain\ShopStatus;
use App\Shared\Domain\Coordinates;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persists domain aggregates for integration tests, behind expressive factories.
 *
 * Aggregates are referenced by identity, so each factory returns the created id.
 * A default manager is created lazily and shared, since most tests don't care
 * which manager owns a shop; pass an explicit one only when it matters.
 *
 * @phpstan-require-extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait CreatesEntities
{
    private ?ManagerId $defaultManagerId = null;

    private function createManager(string $name = 'Test Manager'): ManagerId
    {
        $id = ManagerId::generate();
        $this->persistEntity(Manager::create($id, $name));

        return $id;
    }

    private function createProduct(string $name = 'A product', string $pictureUrl = 'https://example.com/p.jpg'): ProductId
    {
        $id = ProductId::generate();
        $this->persistEntity(Product::create($id, $name, $pictureUrl));

        return $id;
    }

    private function createShop(string $name, ?Coordinates $coordinates = null, ShopStatus $status = ShopStatus::Open, ?ManagerId $managerId = null): ShopId
    {
        $id = ShopId::generate();
        $this->persistEntity(Shop::create(
            $id,
            $name,
            'Some address',
            $coordinates ?? new Coordinates(48.8566, 2.3522),
            $managerId ?? $this->defaultManagerId(),
            $status,
        ));

        return $id;
    }

    private function createStock(ProductId $productId, ShopId $shopId, int $quantity): void
    {
        $this->persistEntity(Stock::create(StockId::generate(), $productId, $shopId, new Quantity($quantity)));
    }

    private function defaultManagerId(): ManagerId
    {
        return $this->defaultManagerId ??= $this->createManager();
    }

    private function persistEntity(object $entity): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();
    }
}
