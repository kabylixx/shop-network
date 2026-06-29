<?php

declare(strict_types=1);

namespace App\Inventory\Application\SetStock;

use App\Catalog\Domain\ProductId;
use App\Inventory\Application\ProductExistence;
use App\Inventory\Application\ShopExistence;
use App\Inventory\Domain\ProductNotFoundException;
use App\Inventory\Domain\Quantity;
use App\Inventory\Domain\ShopNotFoundException;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockId;
use App\Inventory\Domain\StockRepository;
use App\Network\Domain\ShopId;

final readonly class SetStockCommandHandler
{
    public function __construct(
        private StockRepository $stocks,
        private ProductExistence $products,
        private ShopExistence $shops,
    ) {
    }

    /**
     * Upserts each (shop, quantity) couple of the product. Couples not present
     * in the command are left untouched. Existence is validated up front so the
     * whole request is rejected before any write (all or nothing).
     *
     * @return Stock[] the upserted stock lines
     */
    public function __invoke(SetStockCommand $command): array
    {
        $productId = ProductId::fromString($command->productId);
        if (!$this->products->exists($productId)) {
            throw ProductNotFoundException::withId($productId);
        }

        $shopIds = array_map(
            static fn (StockLine $line): ShopId => ShopId::fromString($line->shopId),
            $command->lines,
        );
        $this->ensureShopsExist($shopIds);

        $existing = [];
        foreach ($this->stocks->findAllForProduct($productId) as $stock) {
            $existing[(string) $stock->shopId()] = $stock;
        }

        $upserted = [];
        foreach ($command->lines as $line) {
            $shopId = ShopId::fromString($line->shopId);
            $quantity = new Quantity($line->quantity);

            $stock = $existing[(string) $shopId] ?? null;
            if (null !== $stock) {
                $stock->changeQuantityTo($quantity);
            } else {
                $stock = Stock::create(StockId::generate(), $productId, $shopId, $quantity);
            }

            $upserted[] = $stock;
        }

        $this->stocks->saveAll($upserted);

        return $upserted;
    }

    /**
     * @param ShopId[] $shopIds
     */
    private function ensureShopsExist(array $shopIds): void
    {
        $existing = array_map(strval(...), $this->shops->existing($shopIds));

        $missing = array_filter(
            $shopIds,
            static fn (ShopId $id): bool => !\in_array((string) $id, $existing, true),
        );

        if ([] !== $missing) {
            throw ShopNotFoundException::withIds(array_values($missing));
        }
    }
}
