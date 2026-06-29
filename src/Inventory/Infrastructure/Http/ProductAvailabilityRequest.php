<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use App\Shared\Domain\Coordinates;
use App\Shared\Domain\SearchArea;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ProductAvailabilityRequest
{
    #[Assert\Positive(message: 'The page must be a positive integer.')]
    public int $page = 1;

    #[Assert\Range(notInRangeMessage: 'The limit must be between {{ min }} and {{ max }}.', min: 1, max: 100)]
    public int $limit = 20;

    #[Assert\Range(notInRangeMessage: 'The latitude must be between {{ min }} and {{ max }}.', min: -90, max: 90)]
    public ?float $lat = null;

    #[Assert\Range(notInRangeMessage: 'The longitude must be between {{ min }} and {{ max }}.', min: -180, max: 180)]
    public ?float $lng = null;

    #[Assert\Positive(message: 'The radius must be greater than 0.')]
    public ?float $radius = null;

    /**
     * Geolocation is all-or-nothing: lat, lng and radius must be provided
     * together.
     */
    #[Assert\Callback]
    public function validateGeolocationIsComplete(ExecutionContextInterface $context): void
    {
        $fields = ['lat' => $this->lat, 'lng' => $this->lng, 'radius' => $this->radius];
        $providedCount = \count(array_filter($fields, static fn (?float $value): bool => null !== $value));

        if (0 === $providedCount || 3 === $providedCount) {
            return;
        }

        foreach ($fields as $path => $value) {
            if (null === $value) {
                $context->buildViolation('Geolocation search requires lat, lng and radius together.')
                    ->atPath($path)
                    ->addViolation();
            }
        }
    }

    /**
     * Builds the search area once the request is validated, or null when no
     * geolocation was provided (plain listing sorted by shop name).
     */
    public function toSearchArea(): ?SearchArea
    {
        if (null === $this->lat || null === $this->lng || null === $this->radius) {
            return null;
        }

        return new SearchArea(new Coordinates($this->lat, $this->lng), $this->radius);
    }
}
