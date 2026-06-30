<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Http;

use App\Network\Domain\ShopId;
use App\Tests\Support\CreatesEntities;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class ListStockActionTest extends WebTestCase
{
    use CreatesEntities;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testItReturnsTheDetailPerShopWithoutSummingAcrossShops(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');
        $this->createStock($product, $shopA, 3);
        $this->createStock($product, $shopB, 5);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s,%s', $shopA, $shopB));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(
            [(string) $shopA => 3, (string) $shopB => 5],
            array_column($data['items'], 'quantity', 'shopId'),
        );
    }

    public function testItFiltersByTheRequestedShopsOnly(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');
        $shopC = $this->createShop('Shop C');
        $this->createStock($product, $shopA, 1);
        $this->createStock($product, $shopB, 2);
        $this->createStock($product, $shopC, 3);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s,%s', $shopA, $shopB));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertEqualsCanonicalizing(
            [(string) $shopA, (string) $shopB],
            array_column($data['items'], 'shopId'),
        );
    }

    public function testItReturnsEveryShopWhenNoFilterIsGiven(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');
        $this->createStock($product, $shopA, 1);
        $this->createStock($product, $shopB, 2);

        // Act
        $data = $this->listStock('');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertEqualsCanonicalizing(
            [(string) $shopA, (string) $shopB],
            array_column($data['items'], 'shopId'),
        );
    }

    public function testItExcludesOutOfStockLinesByDefault(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $shopB = $this->createShop('Shop B');
        $this->createStock($product, $shopA, 0);
        $this->createStock($product, $shopB, 7);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s,%s', $shopA, $shopB));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame([(string) $shopB], array_column($data['items'], 'shopId'));
    }

    public function testItIncludesOutOfStockLinesWhenRequested(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $this->createStock($product, $shopA, 0);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s&includeOutOfStock=true', $shopA));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(0, $data['items'][0]['quantity']);
    }

    public function testItExposesTheProductDetailsOfEachLine(): void
    {
        // Arrange
        $product = $this->createProduct('A dress', 'https://example.com/dress.jpg');
        $shopA = $this->createShop('Shop A');
        $this->createStock($product, $shopA, 4);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s', $shopA));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame([
            'productId' => (string) $product,
            'productName' => 'A dress',
            'pictureUrl' => 'https://example.com/dress.jpg',
            'shopId' => (string) $shopA,
            'quantity' => 4,
        ], $data['items'][0]);
    }

    public function testItPaginatesTheResults(): void
    {
        // Arrange
        $shop = $this->createShop('Shop A');
        $this->createStock($this->createProduct('Alpha'), $shop, 1);
        $this->createStock($this->createProduct('Bravo'), $shop, 2);
        $this->createStock($this->createProduct('Charlie'), $shop, 3);

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s&page=2&limit=2', $shop));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['totalPages']);
        self::assertSame(['Charlie'], array_column($data['items'], 'productName'));
    }

    public function testItIgnoresUnknownShopsInTheFilter(): void
    {
        // Arrange
        $product = $this->createProduct('A dress');
        $shopA = $this->createShop('Shop A');
        $this->createStock($product, $shopA, 3);
        $unknownShop = ShopId::generate();

        // Act
        $data = $this->listStock(\sprintf('shopIds=%s,%s', $shopA, $unknownShop));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame([(string) $shopA], array_column($data['items'], 'shopId'));
    }

    public function testItRejectsMalformedShopIds(): void
    {
        // Act
        $data = $this->listStock('shopIds=not-a-uuid');

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('Each shopId must be a valid UUID.', $messagesByPath['shopIds']);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function listStock(string $query): array
    {
        $this->client->request('GET', '/api/stock?'.$query);

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
