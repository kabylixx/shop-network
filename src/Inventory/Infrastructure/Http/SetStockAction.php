<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Http;

use App\Inventory\Application\SetStock\SetStockCommand;
use App\Inventory\Application\SetStock\SetStockCommandHandler;
use App\Inventory\Application\SetStock\StockLine;
use App\Inventory\Domain\Stock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products/{id}/stock', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
final readonly class SetStockAction
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private SetStockCommandHandler $handler,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        /** @var StockLineRequest[] $lines */
        $lines = $this->serializer->deserialize(
            $request->getContent(),
            StockLineRequest::class.'[]',
            'json',
        );

        $setStockRequestDto = new SetStockRequest($lines);

        $violations = $this->validator->validate($setStockRequestDto);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($setStockRequestDto, $violations);
        }

        $stocks = ($this->handler)(
            new SetStockCommand(
                productId: $id,
                lines: array_map(
                    static fn (StockLineRequest $line): StockLine => new StockLine(
                        (string) $line->shopId,
                        (int) $line->quantity,
                    ),
                    $lines,
                ),
            ),
        );

        return new JsonResponse(
            array_map(
                static fn (Stock $stock): array => [
                    'shopId' => (string) $stock->shopId(),
                    'quantity' => $stock->quantity()->value,
                ],
                $stocks,
            ),
            Response::HTTP_OK,
        );
    }
}
