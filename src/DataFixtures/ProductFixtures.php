<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    /**
     * @var list<string>
     */
    private const array PRODUCT_NAMES = [
        'Manteau Will',
        'Pull Gaspard',
        'Robe Sandy',
        'Chemise Olympia',
        'Veste Vince',
        'Jean Margaux',
        'Blouse Mélodie',
        'Pull Désirée',
        'Jupe Suzon',
        'Trench Hélène',
        'Chemisier Étienne',
        'Robe Andy',
        'Ballerines Low Jane',
        'Sac Farrow',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRODUCT_NAMES as $name) {
            $slug = $this->slugify($name);
            $product = Product::create(
                ProductId::generate(),
                $name,
                \sprintf('https://media.szn.com/products/%s.jpg', $slug),
            );

            $manager->persist($product);
            $this->addReference($name, $product);
        }

        $manager->flush();
    }

    private function slugify(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $ascii = false !== $ascii ? $ascii : $name;

        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)) ?? '', '-');
    }
}
