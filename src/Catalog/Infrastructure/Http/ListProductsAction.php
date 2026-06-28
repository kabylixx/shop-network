<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use App\Catalog\Application\ListProducts\ListProductsQuery;
use App\Catalog\Application\ListProducts\ListProductsQueryHandler;
use App\Shared\Application\Pagination;
use App\Shared\Application\SortDirection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', methods: ['GET'])]
final readonly class ListProductsAction
{
    public function __construct(private ListProductsQueryHandler $handler)
    {
    }

    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ListProductsRequest $request = new ListProductsRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new ListProductsQuery(
            search: $request->search,
            sortField: $request->sort,
            direction: SortDirection::from($request->direction),
            pagination: new Pagination($request->page, $request->limit),
        ));

        return new JsonResponse($result);
    }
}
