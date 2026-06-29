<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * The geographic disk to search within — a center and a radius in meters.
 * Center and radius are meaningless apart, so they travel together as a single
 * value; an instance is always a complete, valid area.
 */
final readonly class SearchArea
{
    public function __construct(
        public Coordinates $center,
        public float $radiusInMeters,
    ) {
        if ($radiusInMeters <= 0.0) {
            throw new \InvalidArgumentException(\sprintf('The search radius must be greater than 0, got %s.', $radiusInMeters));
        }
    }
}
