<?php

declare(strict_types=1);

namespace App\Network\Application;

use App\Network\Domain\Shop;

final readonly class ShopView
{
    private function __construct(
        public string $id,
        public string $name,
        public string $address,
        public float $latitude,
        public float $longitude,
        public string $managerId,
        public string $status,
    ) {
    }

    public static function fromShop(Shop $shop): self
    {
        $coordinates = $shop->coordinates();

        return new self(
            (string) $shop->id(),
            $shop->name(),
            $shop->address(),
            $coordinates->latitude,
            $coordinates->longitude,
            (string) $shop->managerId(),
            $shop->status()->value,
        );
    }
}
