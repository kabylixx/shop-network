<?php

declare(strict_types=1);

namespace App\Tests\Inventory\Domain;

use App\Inventory\Domain\Quantity;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class QuantityTest extends TestCase
{
    public function testItAcceptsZeroAsAReferencedOutOfStockProduct(): void
    {
        // Act
        $quantity = new Quantity(0);

        // Assert
        self::assertSame(0, $quantity->value);
    }

    public function testItAcceptsAPositiveQuantity(): void
    {
        // Act
        $quantity = new Quantity(42);

        // Assert
        self::assertSame(42, $quantity->value);
    }

    public function testItRejectsANegativeQuantity(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        new Quantity(-100);
    }
}
