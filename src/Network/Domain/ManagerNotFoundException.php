<?php

declare(strict_types=1);

namespace App\Network\Domain;

use App\Shared\Domain\NotFoundException;

final class ManagerNotFoundException extends NotFoundException
{
    public static function withId(ManagerId $id): self
    {
        return new self(\sprintf('Manager "%s" does not exist.', $id));
    }
}
