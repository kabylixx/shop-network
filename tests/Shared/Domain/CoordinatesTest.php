<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CoordinatesTest extends TestCase
{
    public function testItAcceptsCoordinatesWithinRange(): void
    {
        // Act
        $coordinates = new Coordinates(48.8566, 2.3522);

        // Assert
        self::assertSame(48.8566, $coordinates->latitude);
        self::assertSame(2.3522, $coordinates->longitude);
    }

    #[DataProvider('boundaryCoordinates')]
    public function testItAcceptsTheBoundaryValues(float $latitude, float $longitude): void
    {
        // Act
        $coordinates = new Coordinates($latitude, $longitude);

        // Assert
        self::assertSame($latitude, $coordinates->latitude);
        self::assertSame($longitude, $coordinates->longitude);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function boundaryCoordinates(): iterable
    {
        yield 'south pole / antimeridian west' => [-90.0, -180.0];
        yield 'north pole / antimeridian east' => [90.0, 180.0];
        yield 'null island' => [0.0, 0.0];
    }

    #[DataProvider('latitudeOutOfRange')]
    public function testItRejectsALatitudeOutOfRange(float $latitude): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90');

        // Act
        new Coordinates($latitude, 0.0);
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function latitudeOutOfRange(): iterable
    {
        yield 'just below the minimum' => [-90.001];
        yield 'just above the maximum' => [90.001];
    }

    #[DataProvider('longitudeOutOfRange')]
    public function testItRejectsALongitudeOutOfRange(float $longitude): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Longitude must be between -180 and 180');

        // Act
        new Coordinates(0.0, $longitude);
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function longitudeOutOfRange(): iterable
    {
        yield 'just below the minimum' => [-180.001];
        yield 'just above the maximum' => [180.001];
    }
}
