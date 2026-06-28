<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Http;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Inventory\Domain\Quantity;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockId;
use App\Inventory\Domain\StockRepository;
use App\Network\Domain\Coordinates;
use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use App\Network\Domain\Shop;
use App\Network\Domain\ShopId;
use App\Network\Domain\ShopStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SetStockActionTest extends WebTestCase
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

    public function testItCreatesStockLinesForNewCouples(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');

        // Act
        $data = $this->setStock((string) $productId, [
            ['shopId' => (string) $shopA, 'quantity' => 5],
            ['shopId' => (string) $shopB, 'quantity' => 0],
        ]);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame([
            ['shopId' => (string) $shopA, 'quantity' => 5],
            ['shopId' => (string) $shopB, 'quantity' => 0],
        ], $data);
        self::assertSame([(string) $shopA => 5, (string) $shopB => 0], $this->currentStock($productId));
    }

    public function testItReplacesTheQuantityOfAnExistingCouple(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');
        $this->createStock($productId, $shopA, 2);

        // Act
        $this->setStock((string) $productId, [['shopId' => (string) $shopA, 'quantity' => 5]]);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame([(string) $shopA => 5], $this->currentStock($productId));
    }

    public function testItLeavesCouplesAbsentFromThePayloadUntouched(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');
        $this->createStock($productId, $shopA, 2);
        $this->createStock($productId, $shopB, 9);

        // Act
        $this->setStock((string) $productId, [['shopId' => (string) $shopA, 'quantity' => 5]]);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame([(string) $shopA => 5, (string) $shopB => 9], $this->currentStock($productId));
    }

    public function testItRejectsANegativeQuantity(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');

        // Act
        $data = $this->setStock((string) $productId, [['shopId' => (string) $shopA, 'quantity' => -1]]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('The quantity must be zero or greater.', $messagesByPath['lines[0].quantity']);
    }

    public function testItRejectsDuplicateShopsInThePayload(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');

        // Act
        $data = $this->setStock((string) $productId, [
            ['shopId' => (string) $shopA, 'quantity' => 1],
            ['shopId' => (string) $shopA, 'quantity' => 2],
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('Each shop must appear at most once.', $messagesByPath['lines[1].shopId']);
    }

    public function testItReturns404WhenTheProductDoesNotExist(): void
    {
        // Arrange
        $shopA = $this->createShop('Shop A');
        $unknownProductId = ProductId::generate();

        // Act
        $this->setStock((string) $unknownProductId, [['shopId' => (string) $shopA, 'quantity' => 1]]);

        // Assert
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testItReturns404WhenAShopDoesNotExist(): void
    {
        // Arrange
        $productId = $this->createProduct();
        $shopA = $this->createShop('Shop A');
        $unknownShopId = ShopId::generate();

        // Act
        $this->setStock((string) $productId, [
            ['shopId' => (string) $shopA, 'quantity' => 1],
            ['shopId' => (string) $unknownShopId, 'quantity' => 2],
        ]);

        // Assert
        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->currentStock($productId));
    }

    public function testItReturns404WhenTheProductIdIsNotAUuid(): void
    {
        // Act
        $this->client->request('PUT', '/api/products/not-a-uuid/stock', content: '[]');

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

    private function createProduct(): ProductId
    {
        $id = ProductId::generate();
        $this->entityManager->persist(Product::create($id, 'A product', 'https://example.com/p.jpg'));
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
     * @return array<string, int> quantity indexed by shop id
     */
    private function currentStock(ProductId $productId): array
    {
        $repository = static::getContainer()->get(StockRepository::class);

        $map = [];
        foreach ($repository->findAllForProduct($productId) as $stock) {
            $map[(string) $stock->shopId()] = $stock->quantity()->value;
        }

        return $map;
    }

    /**
     * @param array<int, array{shopId: string, quantity: int}> $lines
     *
     * @return array<mixed>
     *
     * @throws \JsonException
     */
    private function setStock(string $productId, array $lines): array
    {
        $this->client->request(
            'PUT',
            \sprintf('/api/products/%s/stock', $productId),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($lines, JSON_THROW_ON_ERROR),
        );

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
