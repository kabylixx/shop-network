<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use App\Catalog\Application\CreateProduct\CreateProductCommand;
use App\Catalog\Application\CreateProduct\CreateProductCommandHandler;
use App\Catalog\Application\ProductView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', methods: ['POST'])]
final readonly class CreateProductAction
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private CreateProductCommandHandler $handler,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $createProductRequestDto = $this->serializer->deserialize(
            $request->getContent(),
            CreateProductRequest::class,
            'json',
        );

        $violations = $this->validator->validate($createProductRequestDto);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($createProductRequestDto, $violations);
        }

        $product = ($this->handler)(
            new CreateProductCommand(
                (string) $createProductRequestDto->name,
                (string) $createProductRequestDto->pictureUrl
            )
        );

        return new JsonResponse(ProductView::fromProduct($product), Response::HTTP_CREATED);
    }
}
