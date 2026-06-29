<?php

declare(strict_types=1);

namespace App\Network\Application\CreateShop;

use App\Network\Domain\ManagerId;
use App\Network\Domain\ManagerNotFoundException;
use App\Network\Domain\ManagerRepository;
use App\Network\Domain\Shop;
use App\Network\Domain\ShopId;
use App\Network\Domain\ShopRepository;
use App\Network\Domain\ShopStatus;
use App\Shared\Domain\Coordinates;

final readonly class CreateShopCommandHandler
{
    public function __construct(
        private ShopRepository $shops,
        private ManagerRepository $managers,
    ) {
    }

    public function __invoke(CreateShopCommand $command): Shop
    {
        $managerId = ManagerId::fromString($command->managerId);

        if (!$this->managers->exists($managerId)) {
            throw ManagerNotFoundException::withId($managerId);
        }

        $shop = Shop::create(
            ShopId::generate(),
            $command->name,
            $command->address,
            new Coordinates($command->latitude, $command->longitude),
            $managerId,
            ShopStatus::from($command->status),
        );

        $this->shops->save($shop);

        return $shop;
    }
}
