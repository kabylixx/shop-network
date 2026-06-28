<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Domain\Shop;
use App\Network\Domain\ShopRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineShopRepository implements ShopRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Shop $shop): void
    {
        $this->entityManager->persist($shop);
        $this->entityManager->flush();
    }
}
