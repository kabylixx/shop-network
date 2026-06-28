<?php

declare(strict_types=1);

namespace App\Network\Application;

use App\Network\Domain\Manager;

final readonly class ManagerView
{
    private function __construct(
        public string $id,
        public string $name,
    ) {
    }

    public static function fromManager(Manager $manager): self
    {
        return new self(
            (string) $manager->id(),
            $manager->name(),
        );
    }
}
