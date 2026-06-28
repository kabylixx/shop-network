<?php

declare(strict_types=1);

namespace App\Network\Application\SearchShops;

use App\Network\Domain\Coordinates;

/**
 * Read criterion: the disk to search within — a center and a radius in meters.
 * Center and radius are meaningless apart, so they travel together as a single
 * value; an instance is always a complete, valid search area.
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
