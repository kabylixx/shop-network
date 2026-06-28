<?php

declare(strict_types=1);

namespace App\Network\Application\CreateManager;

final readonly class CreateManagerCommand
{
    public function __construct(
        public string $name,
    ) {
    }
}
