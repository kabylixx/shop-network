<?php

declare(strict_types=1);

namespace App\Network\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'manager')]
class Manager
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'manager_id', unique: true)]
        private readonly ManagerId $id,
        #[ORM\Column(length: 150)]
        private readonly string $name,
    ) {
    }

    public static function create(ManagerId $id, string $name): self
    {
        return new self($id, $name);
    }

    public function id(): ManagerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
