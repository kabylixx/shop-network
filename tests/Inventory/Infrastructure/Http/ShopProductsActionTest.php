<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Http;

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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShopProductsActionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ManagerId $managerId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->managerId = $this->persistManager();
    }

    public function testItListsOnlyTheProductsStockedInTheShop(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $coat = $this->createProduct('A coat');
        $shop = $this->createShop('Shop A');
        $otherShop = $this->createShop('Shop B');
        $this->createStock($dress, $shop, 3);
        $this->createStock($coat, $shop, 1);
        $this->createStock($dress, $otherShop, 9); // another shop, must be ignored

        // Act
        $data = $this->shopProducts((string) $shop);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(['A coat', 'A dress'], array_column($data['items'], 'productName'));
        self::assertSame([(string) $shop, (string) $shop], array_column($data['items'], 'shopId'));
    }

    public function testItExcludesOutOfStockByDefault(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $coat = $this->createProduct('A coat');
        $shop = $this->createShop('Shop A');
        $this->createStock($dress, $shop, 5);
        $this->createStock($coat, $shop, 0);

        // Act
        $data = $this->shopProducts((string) $shop);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['A dress'], array_column($data['items'], 'productName'));
    }

    public function testItReturns404WhenTheShopDoesNotExist(): void
    {
        // Arrange
        $unknownShop = ShopId::generate();

        // Act
        $data = $this->shopProducts((string) $unknownShop);

        // Assert
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame(404, $data['status']);
    }

    public function testItReturns404WhenTheShopIdIsNotAUuid(): void
    {
        // Act
        $this->client->request('GET', '/api/shops/not-a-uuid/products');

        // Assert
        self::assertResponseStatusCodeSame(404);
    }

    private function persistManager(): ManagerId
    {
        $id = ManagerId::generate();
        $this->entityManager->persist(Manager::create($id, 'Test Manager'));
        $this->entityManager->flush();

        return $id;
    }

    private function createProduct(string $name): ProductId
    {
        $id = ProductId::generate();
        $this->entityManager->persist(Product::create($id, $name, 'https://example.com/p.jpg'));
        $this->entityManager->flush();

        return $id;
    }

    private function createShop(string $name): ShopId
    {
        $id = ShopId::generate();
        $this->entityManager->persist(Shop::create(
            $id,
            $name,
            'Some address',
            new Coordinates(48.8566, 2.3522),
            $this->managerId,
            ShopStatus::Open,
        ));
        $this->entityManager->flush();

        return $id;
    }

    private function createStock(ProductId $productId, ShopId $shopId, int $quantity): void
    {
        $this->entityManager->persist(
            Stock::create(StockId::generate(), $productId, $shopId, new Quantity($quantity)),
        );
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function shopProducts(string $shopId): array
    {
        $this->client->request('GET', \sprintf('/api/shops/%s/products', $shopId));

        $data = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($data);

        return $data;
    }
}
