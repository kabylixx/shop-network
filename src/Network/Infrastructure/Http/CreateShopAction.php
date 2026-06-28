<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use App\Network\Application\CreateShop\CreateShopCommand;
use App\Network\Application\CreateShop\CreateShopCommandHandler;
use App\Network\Application\ShopView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/shops', methods: ['POST'])]
final readonly class CreateShopAction
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private CreateShopCommandHandler $handler,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $createShopRequestDto = $this->serializer->deserialize(
            $request->getContent(),
            CreateShopRequest::class,
            'json',
        );

        $violations = $this->validator->validate($createShopRequestDto);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($createShopRequestDto, $violations);
        }

        $shop = ($this->handler)(
            new CreateShopCommand(
                name: (string) $createShopRequestDto->name,
                address: (string) $createShopRequestDto->address,
                latitude: (float) $createShopRequestDto->latitude,
                longitude: (float) $createShopRequestDto->longitude,
                managerId: (string) $createShopRequestDto->managerId,
                status: $createShopRequestDto->status,
            ),
        );

        return new JsonResponse(ShopView::fromShop($shop), Response::HTTP_CREATED);
    }
}
