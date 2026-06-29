<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use App\Inventory\Application\GetStockByShops\GetStockByShopsQuery;
use App\Inventory\Application\GetStockByShops\GetStockByShopsQueryHandler;
use App\Network\Domain\ShopId;
use App\Shared\Application\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stock', methods: ['GET'])]
final readonly class ListStockAction
{
    public function __construct(private GetStockByShopsQueryHandler $handler)
    {
    }

    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ListStockRequest $request = new ListStockRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new GetStockByShopsQuery(
            shopIds: $this->parseShopIds($request->shopIds),
            includeOutOfStock: $request->includeOutOfStock,
            pagination: new Pagination($request->page, $request->limit),
        ));

        return new JsonResponse($result);
    }

    /**
     * @return ShopId[]
     */
    private function parseShopIds(?string $shopIds): array
    {
        if (null === $shopIds || '' === trim($shopIds)) {
            return [];
        }

        return array_map(
            static fn (string $rawShopId): ShopId => ShopId::fromString(trim($rawShopId)),
            explode(',', $shopIds),
        );
    }
}
