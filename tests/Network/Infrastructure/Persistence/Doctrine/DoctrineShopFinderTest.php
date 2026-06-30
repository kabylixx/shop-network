<?php

declare(strict_types=1);

namespace App\Tests\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Application\SearchShops\SearchShopsQuery;
use App\Network\Application\ShopFinder;
use App\Network\Application\ShopView;
use App\Network\Domain\ShopStatus;
use App\Shared\Application\Pagination;
use App\Shared\Domain\Coordinates;
use App\Shared\Domain\SearchArea;
use App\Tests\Support\CreatesEntities;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class DoctrineShopFinderTest extends KernelTestCase
{
    use CreatesEntities;

    // Paris city center — the reference point for every search below.
    private const float PARIS_LAT = 48.8566;
    private const float PARIS_LNG = 2.3522;

    private ShopFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->finder = self::getContainer()->get(ShopFinder::class);
    }

    public function testItComputesTheGreatCircleDistanceInMeters(): void
    {
        // Arrange — Lyon is the only shop, searched from Paris
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357));

        // Act
        $shops = $this->search(new SearchArea($this->paris(), 500000.0));

        // Assert
        self::assertCount(1, $shops);
        self::assertNotNull($shops[0]->distance);
        self::assertEqualsWithDelta(392000.0, $shops[0]->distance, 3000.0);
    }

    public function testItSortsFromNearestToFarthest(): void
    {
        // Arrange
        $this->createShop('Marseille', new Coordinates(43.2965, 5.3698)); // ~660 km
        $this->createShop('Versailles', new Coordinates(48.8014, 2.1301)); // ~17 km
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357)); // ~392 km

        // Act
        $shops = $this->search(new SearchArea($this->paris(), 1000000.0));

        // Assert
        self::assertSame(['Versailles', 'Lyon', 'Marseille'], $this->names($shops));
    }

    public function testItExcludesShopsOutsideTheRadius(): void
    {
        // Arrange
        $this->createShop('Versailles', new Coordinates(48.8014, 2.1301)); // ~17 km from Paris
        $this->createShop('Lyon', new Coordinates(45.7640, 4.8357)); // ~392 km from Paris

        // Act
        $shops = $this->search(new SearchArea($this->paris(), 50000.0));

        // Assert
        self::assertSame(['Versailles'], $this->names($shops));
    }

    public function testItExcludesClosedShopsEvenWithinTheRadius(): void
    {
        // Arrange
        $this->createShop('Paris (closed)', $this->paris(), ShopStatus::Closed);
        $this->createShop('Versailles', new Coordinates(48.8014, 2.1301));

        // Act
        $shops = $this->search(new SearchArea($this->paris(), 50000.0));

        // Assert
        self::assertSame(['Versailles'], $this->names($shops));
    }

    private function paris(): Coordinates
    {
        return new Coordinates(self::PARIS_LAT, self::PARIS_LNG);
    }

    /**
     * @return list<ShopView>
     */
    private function search(SearchArea $area): array
    {
        return $this->finder->search(new SearchShopsQuery('', $area, new Pagination(1, 20)))->items;
    }

    /**
     * @param list<ShopView> $shops
     *
     * @return list<string>
     */
    private function names(array $shops): array
    {
        return array_map(static fn (ShopView $view): string => $view->name, $shops);
    }
}
