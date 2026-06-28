<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Persistence\Doctrine;

use App\Network\Domain\ManagerId;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;

/**
 * Maps {@see ManagerId} to a BINARY(16) column (via symfony/uid's base type).
 */
final class ManagerIdType extends AbstractUidType
{
    public const string NAME = 'manager_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getUidClass(): string
    {
        return ManagerId::class;
    }
}
