<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\Coordinates;
use App\Shared\Domain\SearchArea;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SearchAreaTest extends TestCase
{
    public function testItHoldsItsCenterAndRadius(): void
    {
        // Arrange
        $center = new Coordinates(48.8566, 2.3522);

        // Act
        $area = new SearchArea($center, 5000.0);

        // Assert
        self::assertSame($center, $area->center);
        self::assertSame(5000.0, $area->radiusInMeters);
    }

    #[DataProvider('nonPositiveRadii')]
    public function testItRejectsANonPositiveRadius(float $radius): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The search radius must be greater than 0');

        // Act
        new SearchArea(new Coordinates(0.0, 0.0), $radius);
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function nonPositiveRadii(): iterable
    {
        yield 'zero' => [0.0];
        yield 'negative' => [-1.0];
    }
}
