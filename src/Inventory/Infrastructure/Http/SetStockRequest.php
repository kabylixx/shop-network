<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class SetStockRequest
{
    /**
     * @param StockLineRequest[] $lines
     */
    public function __construct(
        #[Assert\Count(
            min: 1,
            max: 100,
            minMessage: 'At least one stock line is required.',
            maxMessage: 'A request cannot set more than {{ limit }} stock lines.',
        )]
        #[Assert\Valid]
        public array $lines = [],
    ) {
    }

    /**
     * A product may only appear once per shop, so each shop id must be unique
     * within the payload (matching the (shop, product) uniqueness invariant).
     */
    #[Assert\Callback]
    public function validateShopIdsAreUnique(ExecutionContextInterface $context): void
    {
        $seen = [];
        foreach ($this->lines as $index => $line) {
            $shopId = $line->shopId;
            if (null === $shopId) {
                continue;
            }

            if (isset($seen[$shopId])) {
                $context->buildViolation('Each shop must appear at most once.')
                    ->atPath(\sprintf('lines[%d].shopId', $index))
                    ->addViolation();
            }

            $seen[$shopId] = true;
        }
    }
}
