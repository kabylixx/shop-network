<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Http;

use App\Network\Domain\ShopId;
use App\Tests\Support\CreatesEntities;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class ShopProductsActionTest extends WebTestCase
{
    use CreatesEntities;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
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
