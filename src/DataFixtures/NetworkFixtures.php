<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Network\Domain\Coordinates;
use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use App\Network\Domain\Shop;
use App\Network\Domain\ShopId;
use App\Network\Domain\ShopStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NetworkFixtures extends Fixture
{
    /**
     * @var array<string, string>
     */
    private const array MANAGERS = [
        'amelie' => 'Amélie Laurent',
        'hugo' => 'Hugo Moreau',
        'ines' => 'Inès Garnier',
    ];

    /**
     * @var list<array{name: string, address: string, lat: float, lng: float, manager: string, status: ShopStatus}>
     */
    private const array SHOPS = [
        ['name' => 'Paris Sentier', 'address' => '1 rue Saint-Fiacre, 75002 Paris', 'lat' => 48.8693, 'lng' => 2.3470, 'manager' => 'amelie', 'status' => ShopStatus::Open],
        ['name' => 'Paris Marais', 'address' => '12 rue de Rivoli, 75004 Paris', 'lat' => 48.8559, 'lng' => 2.3601, 'manager' => 'amelie', 'status' => ShopStatus::Open],
        ['name' => 'Boulogne-Billancourt', 'address' => '120 route de la Reine, 92100 Boulogne-Billancourt', 'lat' => 48.8365, 'lng' => 2.2402, 'manager' => 'amelie', 'status' => ShopStatus::Closed],
        ['name' => 'Lyon Presqu\'île', 'address' => '40 rue de la République, 69002 Lyon', 'lat' => 45.7640, 'lng' => 4.8357, 'manager' => 'hugo', 'status' => ShopStatus::Open],
        ['name' => 'Bordeaux Centre', 'address' => '15 cours de l\'Intendance, 33000 Bordeaux', 'lat' => 44.8412, 'lng' => -0.5805, 'manager' => 'hugo', 'status' => ShopStatus::Open],
        ['name' => 'Marseille Vieux-Port', 'address' => '1 La Canebière, 13001 Marseille', 'lat' => 43.2965, 'lng' => 5.3698, 'manager' => 'hugo', 'status' => ShopStatus::Open],
        ['name' => 'Toulouse Capitole', 'address' => 'place du Capitole, 31000 Toulouse', 'lat' => 43.6045, 'lng' => 1.4442, 'manager' => 'ines', 'status' => ShopStatus::Open],
        ['name' => 'Nantes Graslin', 'address' => 'place Graslin, 44000 Nantes', 'lat' => 47.2133, 'lng' => -1.5610, 'manager' => 'ines', 'status' => ShopStatus::Open],
        ['name' => 'Lille Grand-Place', 'address' => 'place du Général de Gaulle, 59000 Lille', 'lat' => 50.6366, 'lng' => 3.0635, 'manager' => 'ines', 'status' => ShopStatus::Open],
        ['name' => 'Strasbourg Cathédrale', 'address' => 'place de la Cathédrale, 67000 Strasbourg', 'lat' => 48.5818, 'lng' => 7.7509, 'manager' => 'ines', 'status' => ShopStatus::Closed],
    ];

    public function load(ObjectManager $manager): void
    {
        $managerIds = [];
        foreach (self::MANAGERS as $handle => $name) {
            $id = ManagerId::generate();
            $manager->persist(Manager::create($id, $name));
            $managerIds[$handle] = $id;
        }

        foreach (self::SHOPS as $shop) {
            $entity = Shop::create(
                ShopId::generate(),
                $shop['name'],
                $shop['address'],
                new Coordinates($shop['lat'], $shop['lng']),
                $managerIds[$shop['manager']],
                $shop['status'],
            );

            $manager->persist($entity);
            $this->addReference($shop['name'], $entity);
        }

        $manager->flush();
    }
}
