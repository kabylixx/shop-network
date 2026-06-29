<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use App\Catalog\Domain\ProductId;
use App\Inventory\Application\GetProductAvailability\GetProductAvailabilityQuery;
use App\Inventory\Application\GetProductAvailability\GetProductAvailabilityQueryHandler;
use App\Shared\Application\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/availability', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
final readonly class ProductAvailabilityAction
{
    public function __construct(private GetProductAvailabilityQueryHandler $handler)
    {
    }

    public function __invoke(
        string $id,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ProductAvailabilityRequest $request = new ProductAvailabilityRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new GetProductAvailabilityQuery(
            productId: ProductId::fromString($id),
            area: $request->toSearchArea(),
            pagination: new Pagination($request->page, $request->limit),
        ));

        return new JsonResponse($result);
    }
}
