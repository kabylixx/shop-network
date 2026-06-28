<?php

declare(strict_types=1);

namespace App\Network\Domain;

interface ManagerRepository
{
    public function save(Manager $manager): void;

    public function exists(ManagerId $id): bool;
}
