<?php

declare(strict_types=1);

namespace App\Inventory\Application;

final readonly class AvailabilityView
{
    private function __construct(
        public string $shopId,
        public string $shopName,
        public string $address,
        public float $latitude,
        public float $longitude,
        public string $status,
        public int $quantity,
        public ?float $distance,
    ) {
    }

    public static function fromRow(
        string $shopId,
        string $shopName,
        string $address,
        float $latitude,
        float $longitude,
        string $status,
        int $quantity,
        ?float $distance,
    ): self {
        return new self($shopId, $shopName, $address, $latitude, $longitude, $status, $quantity, $distance);
    }
}
