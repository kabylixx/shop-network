<?php

declare(strict_types=1);

namespace App\Network\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'manager')]
class Manager
{
    public const int NAME_MAX_LENGTH = 150;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'manager_id', unique: true)]
        private readonly ManagerId $id,
        #[ORM\Column(length: self::NAME_MAX_LENGTH)]
        private readonly string $name,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('The manager name must not be blank.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('The manager name must not exceed %d characters.', self::NAME_MAX_LENGTH));
        }
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
