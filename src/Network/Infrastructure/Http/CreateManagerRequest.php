<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use App\Network\Domain\Manager;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateManagerRequest
{
    #[Assert\NotBlank(message: 'The manager name is required.')]
    #[Assert\Length(max: Manager::NAME_MAX_LENGTH, maxMessage: 'The manager name must not exceed {{ limit }} characters.')]
    public ?string $name = null;
}
