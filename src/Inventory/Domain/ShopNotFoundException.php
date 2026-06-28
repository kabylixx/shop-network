<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

use App\Network\Domain\ShopId;
use App\Shared\Domain\NotFoundException;

final class ShopNotFoundException extends NotFoundException
{
    /**
     * @param ShopId[] $ids the shop ids that could not be found
     */
    public static function withIds(array $ids): self
    {
        $list = implode('", "', array_map(strval(...), $ids));

        return new self(\sprintf('Shops "%s" do not exist.', $list));
    }
}
