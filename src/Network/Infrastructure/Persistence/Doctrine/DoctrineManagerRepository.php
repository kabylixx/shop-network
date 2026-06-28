<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use App\Network\Domain\ManagerRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineManagerRepository implements ManagerRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Manager $manager): void
    {
        $this->entityManager->persist($manager);
        $this->entityManager->flush();
    }

    public function exists(ManagerId $id): bool
    {
        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Manager::class, 'm')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
