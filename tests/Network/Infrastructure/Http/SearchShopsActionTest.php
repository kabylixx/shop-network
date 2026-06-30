<?php

declare(strict_types=1);

namespace App\Tests\Network\Infrastructure\Http;

use App\Network\Domain\ShopStatus;
use App\Shared\Domain\Coordinates;
use App\Tests\Support\CreatesEntities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class SearchShopsActionTest extends WebTestCase
{
    use CreatesEntities;

    private const float PARIS_LAT = 48.8566;
    private const float PARIS_LNG = 2.3522;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testItListsOpenShopsSortedByNameWithoutGeolocation(): void
    {
        // Arrange
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357));
        $this->createShop('Bordeaux', new Coordinates(44.8412, -0.5805));
        $this->createShop('Closed shop', new Coordinates(self::PARIS_LAT, self::PARIS_LNG), ShopStatus::Closed);

        // Act
        $data = $this->searchShops('');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(['Bordeaux', 'Lyon'], array_column($data['items'], 'name'));
        self::assertNull($data['items'][0]['distance']);
    }

    public function testItSortsByDistanceAndReturnsItInMeters(): void
    {
        // Arrange
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357)); // ~392 km from Paris
        $this->createShop('Versailles', new Coordinates(48.8014, 2.1301)); // ~17 km from Paris

        // Act
        $data = $this->searchShops(\sprintf('lat=%s&lng=%s&radius=500000', self::PARIS_LAT, self::PARIS_LNG));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(['Versailles', 'Lyon'], array_column($data['items'], 'name'));
        self::assertEqualsWithDelta(392_000.0, $data['items'][1]['distance'], 3000.0);
    }

    public function testItExcludesShopsOutsideTheRadius(): void
    {
        // Arrange
        $this->createShop('Versailles', new Coordinates(48.8014, 2.1301)); // ~17 km
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357)); // ~392 km

        // Act
        $data = $this->searchShops(\sprintf('lat=%s&lng=%s&radius=50000', self::PARIS_LAT, self::PARIS_LNG));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['Versailles'], array_column($data['items'], 'name'));
    }

    public function testItCombinesNameAndGeolocation(): void
    {
        // Arrange
        $this->createShop('Sézane Versailles', new Coordinates(48.8014, 2.1301)); // matches name, in radius
        $this->createShop('Sézane Lyon', new Coordinates(45.7640, 4.8357)); // matches name, out of radius
        $this->createShop('Autre Versailles', new Coordinates(48.8014, 2.1301)); // in radius, name mismatch

        // Act
        $data = 'sézane'
                |> urlencode(...)
                |> (fn (string $x): string => \sprintf('search=%s&lat=%s&lng=%s&radius=50000', $x, self::PARIS_LAT, self::PARIS_LNG))
                |> $this->searchShops(...);

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(['Sézane Versailles'], array_column($data['items'], 'name'));
    }

    public function testItPaginatesTheResults(): void
    {
        // Arrange
        $this->createShop('Alpha', new Coordinates(48.0, 2.0));
        $this->createShop('Bravo', new Coordinates(48.0, 2.0));
        $this->createShop('Charlie', new Coordinates(48.0, 2.0));

        // Act
        $data = $this->searchShops('page=2&limit=2');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['totalPages']);
        self::assertSame(['Charlie'], array_column($data['items'], 'name'));
    }

    public function testItRejectsAnIncompleteGeolocationTrio(): void
    {
        // Act
        $data = $this->searchShops(\sprintf('lat=%s', self::PARIS_LAT));

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('Geolocation search requires lat, lng and radius together.', $messagesByPath['lng']);
        self::assertSame('Geolocation search requires lat, lng and radius together.', $messagesByPath['radius']);
    }

    /**
     * @param array<string, string> $expectedViolationMessages
     *
     * @throws \JsonException
     */
    #[DataProvider('invalidQueryParameters')]
    public function testItRejectsInvalidQueryParameters(string $query, array $expectedViolationMessages): void
    {
        // Act
        $data = $this->searchShops($query);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        foreach ($expectedViolationMessages as $path => $message) {
            self::assertSame($message, $messagesByPath[$path] ?? null);
        }
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function invalidQueryParameters(): iterable
    {
        yield 'latitude out of range' => ['lat=120&lng=2&radius=1000', ['lat' => 'The latitude must be between -90 and 90.']];
        yield 'longitude out of range' => ['lat=48&lng=200&radius=1000', ['lng' => 'The longitude must be between -180 and 180.']];
        yield 'non-positive radius' => ['lat=48&lng=2&radius=0', ['radius' => 'The radius must be greater than 0.']];
        yield 'page below 1' => ['page=0', ['page' => 'The page must be a positive integer.']];
        yield 'limit above cap' => ['limit=999', ['limit' => 'The limit must be between 1 and 100.']];
        yield 'search too long' => ['search='.str_repeat('a', 256), ['search' => 'The search term must not exceed 255 characters.']];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function searchShops(string $query): array
    {
        $this->client->request('GET', '/api/shops?'.$query);

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
