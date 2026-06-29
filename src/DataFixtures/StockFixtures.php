<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Catalog\Domain\Product;
use App\Inventory\Domain\Quantity;
use App\Inventory\Domain\Stock;
use App\Inventory\Domain\StockId;
use App\Network\Domain\Shop;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var list<array{product: string, shop: string, quantity: int}>
     */
    private const array STOCK = [
        ['product' => 'Robe Sandy', 'shop' => 'Paris Sentier', 'quantity' => 8],
        ['product' => 'Robe Sandy', 'shop' => 'Paris Marais', 'quantity' => 3],
        ['product' => 'Robe Sandy', 'shop' => 'Lyon Presqu\'île', 'quantity' => 0],
        ['product' => 'Robe Sandy', 'shop' => 'Toulouse Capitole', 'quantity' => 5],
        ['product' => 'Robe Sandy', 'shop' => 'Nantes Graslin', 'quantity' => 2],
        ['product' => 'Robe Sandy', 'shop' => 'Boulogne-Billancourt', 'quantity' => 4],

        ['product' => 'Manteau Will', 'shop' => 'Paris Marais', 'quantity' => 5],
        ['product' => 'Manteau Will', 'shop' => 'Bordeaux Centre', 'quantity' => 2],
        ['product' => 'Manteau Will', 'shop' => 'Lille Grand-Place', 'quantity' => 9],

        ['product' => 'Sac Farrow', 'shop' => 'Paris Sentier', 'quantity' => 12],
        ['product' => 'Sac Farrow', 'shop' => 'Toulouse Capitole', 'quantity' => 0],
        ['product' => 'Sac Farrow', 'shop' => 'Nantes Graslin', 'quantity' => 4],

        ['product' => 'Jean Margaux', 'shop' => 'Lyon Presqu\'île', 'quantity' => 7],
        ['product' => 'Jean Margaux', 'shop' => 'Marseille Vieux-Port', 'quantity' => 1],
        ['product' => 'Jean Margaux', 'shop' => 'Strasbourg Cathédrale', 'quantity' => 6],

        ['product' => 'Pull Gaspard', 'shop' => 'Paris Sentier', 'quantity' => 6],
        ['product' => 'Pull Gaspard', 'shop' => 'Nantes Graslin', 'quantity' => 2],

        ['product' => 'Robe Andy', 'shop' => 'Paris Marais', 'quantity' => 4],
        ['product' => 'Robe Andy', 'shop' => 'Bordeaux Centre', 'quantity' => 0],

        ['product' => 'Trench Hélène', 'shop' => 'Lille Grand-Place', 'quantity' => 3],
        ['product' => 'Trench Hélène', 'shop' => 'Toulouse Capitole', 'quantity' => 5],

        ['product' => 'Ballerines Low Jane', 'shop' => 'Marseille Vieux-Port', 'quantity' => 10],
        ['product' => 'Ballerines Low Jane', 'shop' => 'Paris Sentier', 'quantity' => 2],

        ['product' => 'Chemise Olympia', 'shop' => 'Bordeaux Centre', 'quantity' => 7],
        ['product' => 'Chemise Olympia', 'shop' => 'Strasbourg Cathédrale', 'quantity' => 0],

        ['product' => 'Blouse Mélodie', 'shop' => 'Nantes Graslin', 'quantity' => 4],
        ['product' => 'Blouse Mélodie', 'shop' => 'Lyon Presqu\'île', 'quantity' => 3],

        ['product' => 'Veste Vince', 'shop' => 'Lille Grand-Place', 'quantity' => 6],
        ['product' => 'Veste Vince', 'shop' => 'Marseille Vieux-Port', 'quantity' => 0],

        ['product' => 'Pull Désirée', 'shop' => 'Paris Marais', 'quantity' => 7],
        ['product' => 'Pull Désirée', 'shop' => 'Boulogne-Billancourt', 'quantity' => 1],

        ['product' => 'Jupe Suzon', 'shop' => 'Toulouse Capitole', 'quantity' => 4],
        ['product' => 'Jupe Suzon', 'shop' => 'Lyon Presqu\'île', 'quantity' => 2],

        ['product' => 'Chemisier Étienne', 'shop' => 'Lille Grand-Place', 'quantity' => 5],
        ['product' => 'Chemisier Étienne', 'shop' => 'Bordeaux Centre', 'quantity' => 3],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::STOCK as $line) {
            $product = $this->getReference($line['product'], Product::class);
            $shop = $this->getReference($line['shop'], Shop::class);

            $manager->persist(Stock::create(
                StockId::generate(),
                $product->id(),
                $shop->id(),
                new Quantity($line['quantity']),
            ));
        }

        $manager->flush();
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [ProductFixtures::class, NetworkFixtures::class];
    }
}
