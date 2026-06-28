<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateManagerRequest
{
    #[Assert\NotBlank(message: 'The manager name is required.')]
    #[Assert\Length(max: 150, maxMessage: 'The manager name must not exceed {{ limit }} characters.')]
    public ?string $name = null;
}
