<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\ProductId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ProductIdTest extends TestCase
{
    public function testItGeneratesAValidUuid(): void
    {
        // Act
        $id = ProductId::generate();

        // Assert
        self::assertTrue(Uuid::isValid((string) $id));
    }

    public function testItReconstructsAKnownIdFromString(): void
    {
        // Arrange
        $value = '019f0df9-eef9-79e5-9d74-20e92f1721f7';

        // Act
        $id = ProductId::fromString($value);

        // Assert
        self::assertSame($value, (string) $id);
    }
}
