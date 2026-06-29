<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Geographic point. A pure value object with bounded latitude/longitude,
 * shared across modules — Network stores a shop's location, Inventory uses it as
 * the center of an availability search.
 */
final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new \InvalidArgumentException(\sprintf('Latitude must be between -90 and 90, got %s.', $latitude));
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new \InvalidArgumentException(\sprintf('Longitude must be between -180 and 180, got %s.', $longitude));
        }
    }
}
