<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product')]
#[ORM\Index(name: 'idx_product_name', columns: ['name'])]
class Product
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'product_id', unique: true)]
        private readonly ProductId $id,
        #[ORM\Column(length: 255)]
        private readonly string $name,
        #[ORM\Column(length: 2000)]
        private readonly string $pictureUrl,
    ) {
    }

    public static function create(ProductId $id, string $name, string $pictureUrl): self
    {
        return new self($id, $name, $pictureUrl);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function pictureUrl(): string
    {
        return $this->pictureUrl;
    }
}
