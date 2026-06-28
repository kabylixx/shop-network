<?php

declare(strict_types=1);

namespace App\Tests\Network\Domain;

use App\Network\Domain\Coordinates;
use App\Network\Domain\ManagerId;
use App\Network\Domain\Shop;
use App\Network\Domain\ShopId;
use App\Network\Domain\ShopStatus;
use PHPUnit\Framework\TestCase;

final class ShopTest extends TestCase
{
    public function testItCreatesAShopWithAValidName(): void
    {
        // Act
        $shop = $this->createShopNamed('Boutique Marais');

        // Assert
        self::assertSame('Boutique Marais', $shop->name());
    }

    public function testItAcceptsANameAtTheMaximumLength(): void
    {
        // Arrange
        $name = str_repeat('a', Shop::NAME_MAX_LENGTH);

        // Act
        $shop = $this->createShopNamed($name);

        // Assert
        self::assertSame($name, $shop->name());
    }

    public function testItRejectsANameLongerThanTheMaximum(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed 150 characters');

        // Act
        $this->createShopNamed(str_repeat('a', Shop::NAME_MAX_LENGTH + 1));
    }

    public function testItRejectsABlankName(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be blank');

        // Act
        $this->createShopNamed('   ');
    }

    private function createShopNamed(string $name): Shop
    {
        return Shop::create(
            ShopId::generate(),
            $name,
            '12 rue de Rivoli, Paris',
            new Coordinates(48.8566, 2.3522),
            ManagerId::generate(),
            ShopStatus::Open,
        );
    }
}
