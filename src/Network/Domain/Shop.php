<?php

declare(strict_types=1);

namespace App\Network\Domain;

use App\Shared\Domain\Coordinates;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shop')]
class Shop
{
    public const int NAME_MAX_LENGTH = 150;

    #[ORM\Column(type: 'float')]
    private readonly float $latitude;

    #[ORM\Column(type: 'float')]
    private readonly float $longitude;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'shop_id', unique: true)]
        private readonly ShopId $id,
        #[ORM\Column(length: self::NAME_MAX_LENGTH)]
        private readonly string $name,
        #[ORM\Column(length: 255)]
        private readonly string $address,
        Coordinates $coordinates,
        #[ORM\Column(type: 'manager_id')]
        private readonly ManagerId $managerId,
        #[ORM\Column(length: 16, enumType: ShopStatus::class)]
        private readonly ShopStatus $status,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('The shop name must not be blank.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('The shop name must not exceed %d characters.', self::NAME_MAX_LENGTH));
        }

        $this->latitude = $coordinates->latitude;
        $this->longitude = $coordinates->longitude;
    }

    public static function create(
        ShopId $id,
        string $name,
        string $address,
        Coordinates $coordinates,
        ManagerId $managerId,
        ShopStatus $status,
    ): self {
        return new self($id, $name, $address, $coordinates, $managerId, $status);
    }

    public function id(): ShopId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function coordinates(): Coordinates
    {
        return new Coordinates($this->latitude, $this->longitude);
    }

    public function managerId(): ManagerId
    {
        return $this->managerId;
    }

    public function status(): ShopStatus
    {
        return $this->status;
    }
}
