<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use App\Inventory\Application\GetShopProducts\GetShopProductsQuery;
use App\Inventory\Application\GetShopProducts\GetShopProductsQueryHandler;
use App\Network\Domain\ShopId;
use App\Shared\Application\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/shops/{id}/products', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
final readonly class ShopProductsAction
{
    public function __construct(private GetShopProductsQueryHandler $handler)
    {
    }

    public function __invoke(
        string $id,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ShopProductsRequest $request = new ShopProductsRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new GetShopProductsQuery(
            shopId: ShopId::fromString($id),
            includeOutOfStock: $request->includeOutOfStock,
            pagination: new Pagination($request->page, $request->limit),
        ));

        return new JsonResponse($result);
    }
}
