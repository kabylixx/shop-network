<?php

declare(strict_types=1);

namespace App\Network\Application\CreateShop;

final readonly class CreateShopCommand
{
    public function __construct(
        public string $name,
        public string $address,
        public float $latitude,
        public float $longitude,
        public string $managerId,
        public string $status,
    ) {
    }
}
