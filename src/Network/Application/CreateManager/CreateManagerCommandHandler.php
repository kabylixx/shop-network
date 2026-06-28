<?php

declare(strict_types=1);

namespace App\Network\Application\CreateManager;

use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use App\Network\Domain\ManagerRepository;

final readonly class CreateManagerCommandHandler
{
    public function __construct(private ManagerRepository $managers)
    {
    }

    public function __invoke(CreateManagerCommand $command): Manager
    {
        $manager = Manager::create(ManagerId::generate(), $command->name);

        $this->managers->save($manager);

        return $manager;
    }
}
