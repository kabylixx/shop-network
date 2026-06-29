<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Infrastructure\Http;

use App\Catalog\Domain\ProductId;
use App\Network\Domain\ShopStatus;
use App\Shared\Domain\Coordinates;
use App\Tests\Support\CreatesEntities;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductAvailabilityActionTest extends WebTestCase
{
    use CreatesEntities;

    // Paris (Notre-Dame) — the search center used by the geolocated cases.
    private const float CENTER_LAT = 48.8530;
    private const float CENTER_LNG = 2.3499;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testItListsOpenShopsStockingTheProductSortedByName(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $shopB = $this->createShop('Shop B');
        $shopA = $this->createShop('Shop A');
        $this->createStock($dress, $shopB, 4);
        $this->createStock($dress, $shopA, 7);

        // Act
        $data = $this->availability((string) $dress);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(['Shop A', 'Shop B'], array_column($data['items'], 'shopName'));
        self::assertSame([7, 4], array_column($data['items'], 'quantity'));
        self::assertNull($data['items'][0]['distance']);
    }

    public function testItIgnoresShopsThatDoNotStockTheProduct(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $coat = $this->createProduct('A coat');
        $shop = $this->createShop('Shop A');
        $otherShop = $this->createShop('Shop B');
        $this->createStock($dress, $shop, 3);
        $this->createStock($coat, $otherShop, 9); // another product, must be ignored

        // Act
        $data = $this->availability((string) $dress);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['Shop A'], array_column($data['items'], 'shopName'));
    }

    public function testItReturnsAnEmptyPageWhenTheProductIsStockedNowhere(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $this->createShop('Shop A');

        // Act
        $data = $this->availability((string) $dress);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(0, $data['total']);
        self::assertSame([], $data['items']);
    }

    public function testItExcludesShopsWhereTheProductIsOutOfStock(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $inStock = $this->createShop('In stock');
        $rupture = $this->createShop('Out of stock');
        $this->createStock($dress, $inStock, 5);
        $this->createStock($dress, $rupture, 0);

        // Act
        $data = $this->availability((string) $dress);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['In stock'], array_column($data['items'], 'shopName'));
    }

    public function testItExcludesClosedShops(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $open = $this->createShop('Open shop');
        $closed = $this->createShop('Closed shop', status: ShopStatus::Closed);
        $this->createStock($dress, $open, 2);
        $this->createStock($dress, $closed, 8);

        // Act
        $data = $this->availability((string) $dress);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['Open shop'], array_column($data['items'], 'shopName'));
    }

    public function testItReturnsOnlyShopsWithinRadiusSortedByDistance(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');
        $near = $this->createShop('Near shop', new Coordinates(48.8559, 2.3601)); // ~700 m from center
        $mid = $this->createShop('Mid shop', new Coordinates(48.8738, 2.2950));   // ~5 km from center
        $far = $this->createShop('Far shop', new Coordinates(45.7640, 4.8357));   // Lyon, ~390 km
        $this->createStock($dress, $mid, 1);
        $this->createStock($dress, $near, 1);
        $this->createStock($dress, $far, 1);

        // Act
        $data = $this->availability((string) $dress, [
            'lat' => self::CENTER_LAT,
            'lng' => self::CENTER_LNG,
            'radius' => 10000,
        ]);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(['Near shop', 'Mid shop'], array_column($data['items'], 'shopName'));
        self::assertNotNull($data['items'][0]['distance']);
        self::assertLessThan($data['items'][1]['distance'], $data['items'][0]['distance']);
    }

    public function testItReturns404WhenTheProductDoesNotExist(): void
    {
        // Arrange
        $unknownProduct = ProductId::generate();

        // Act
        $data = $this->availability((string) $unknownProduct);

        // Assert
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame(404, $data['status']);
    }

    public function testItReturns404WhenTheProductIdIsNotAUuid(): void
    {
        // Act
        $this->client->request('GET', '/api/products/not-a-uuid/availability');

        // Assert
        self::assertResponseStatusCodeSame(404);
    }

    public function testItReturns422WhenGeolocationIsIncomplete(): void
    {
        // Arrange
        $dress = $this->createProduct('A dress');

        // Act — radius without lat/lng is meaningless
        $data = $this->availability((string) $dress, ['radius' => 5000]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame(422, $data['status']);
    }

    /**
     * @param array<string, float|int> $query
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function availability(string $productId, array $query = []): array
    {
        $this->client->request('GET', \sprintf('/api/products/%s/availability', $productId), $query);

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
