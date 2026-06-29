<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ListStockRequest
{
    #[Assert\Positive(message: 'The page must be a positive integer.')]
    public int $page = 1;

    #[Assert\Range(notInRangeMessage: 'The limit must be between {{ min }} and {{ max }}.', min: 1, max: 100)]
    public int $limit = 20;

    /**
     * Comma-separated list of shop UUIDs; null/empty means every shop.
     */
    public ?string $shopIds = null;

    public bool $includeOutOfStock = false;

    #[Assert\Callback]
    public function validateShopIds(ExecutionContextInterface $context): void
    {
        if (null === $this->shopIds || '' === trim($this->shopIds)) {
            return;
        }

        foreach (explode(',', $this->shopIds) as $rawShopId) {
            if (!Uuid::isValid(trim($rawShopId))) {
                $context->buildViolation('Each shopId must be a valid UUID.')
                    ->atPath('shopIds')
                    ->addViolation();
            }
        }
    }
}
