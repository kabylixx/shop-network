<?php

declare(strict_types=1);

namespace App\Inventory\Domain;

use App\Catalog\Domain\ProductId;
use App\Network\Domain\ShopId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock')]
#[ORM\UniqueConstraint(name: 'uniq_stock_shop_product', columns: ['shop_id', 'product_id'])]
class Stock
{
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $quantity;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'stock_id', unique: true)]
        private readonly StockId $id,
        #[ORM\Column(type: 'product_id')]
        private readonly ProductId $productId,
        #[ORM\Column(type: 'shop_id')]
        private readonly ShopId $shopId,
        Quantity $quantity,
    ) {
        $this->quantity = $quantity->value;
    }

    public static function create(StockId $id, ProductId $productId, ShopId $shopId, Quantity $quantity): self
    {
        return new self($id, $productId, $shopId, $quantity);
    }

    /**
     * Replaces the held quantity (PUT upsert semantics on an existing couple).
     */
    public function changeQuantityTo(Quantity $quantity): void
    {
        $this->quantity = $quantity->value;
    }

    public function id(): StockId
    {
        return $this->id;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function quantity(): Quantity
    {
        return new Quantity($this->quantity);
    }
}
