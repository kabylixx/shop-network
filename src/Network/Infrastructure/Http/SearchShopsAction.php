<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use App\Network\Application\SearchShops\SearchArea;
use App\Network\Application\SearchShops\SearchShopsQuery;
use App\Network\Application\SearchShops\SearchShopsQueryHandler;
use App\Network\Domain\Coordinates;
use App\Shared\Application\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shops', methods: ['GET'])]
final readonly class SearchShopsAction
{
    public function __construct(private SearchShopsQueryHandler $handler)
    {
    }

    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        SearchShopsRequest $request = new SearchShopsRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new SearchShopsQuery(
            search: $request->search,
            area: $this->toSearchArea($request),
            pagination: new Pagination($request->page, $request->limit),
        ));

        return new JsonResponse($result);
    }

    private function toSearchArea(SearchShopsRequest $request): ?SearchArea
    {
        if (null === $request->lat || null === $request->lng || null === $request->radius) {
            return null;
        }

        return new SearchArea(
            new Coordinates($request->lat, $request->lng),
            $request->radius,
        );
    }
}
