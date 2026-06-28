<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use App\Network\Application\CreateManager\CreateManagerCommand;
use App\Network\Application\CreateManager\CreateManagerCommandHandler;
use App\Network\Application\ManagerView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/managers', methods: ['POST'])]
final readonly class CreateManagerAction
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private CreateManagerCommandHandler $handler,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $createManagerRequestDto = $this->serializer->deserialize(
            $request->getContent(),
            CreateManagerRequest::class,
            'json',
        );

        $violations = $this->validator->validate($createManagerRequestDto);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($createManagerRequestDto, $violations);
        }

        $manager = ($this->handler)(
            new CreateManagerCommand((string) $createManagerRequestDto->name),
        );

        return new JsonResponse(ManagerView::fromManager($manager), Response::HTTP_CREATED);
    }
}
