<?php

declare(strict_types=1);

namespace App\Tests\Network\Domain;

use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use PHPUnit\Framework\TestCase;

final class ManagerTest extends TestCase
{
    public function testItCreatesAManagerWithAValidName(): void
    {
        // Act
        $manager = Manager::create(ManagerId::generate(), 'Amélie Poulain');

        // Assert
        self::assertSame('Amélie Poulain', $manager->name());
    }

    public function testItAcceptsANameAtTheMaximumLength(): void
    {
        // Arrange
        $name = str_repeat('a', Manager::NAME_MAX_LENGTH);

        // Act
        $manager = Manager::create(ManagerId::generate(), $name);

        // Assert
        self::assertSame($name, $manager->name());
    }

    public function testItRejectsANameLongerThanTheMaximum(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed 150 characters');

        // Act
        Manager::create(ManagerId::generate(), str_repeat('a', Manager::NAME_MAX_LENGTH + 1));
    }

    public function testItRejectsABlankName(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be blank');

        // Act
        Manager::create(ManagerId::generate(), '   ');
    }
}
