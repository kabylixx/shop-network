<?php

declare(strict_types=1);

namespace App\Network\Domain;

interface ShopRepository
{
    public function save(Shop $shop): void;
}
