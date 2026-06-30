<?php

declare(strict_types=1);

namespace App\Tests\Network\Infrastructure\Http;

use App\Network\Domain\Manager;
use App\Network\Domain\ManagerId;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

#[Group('functional')]
final class CreateShopActionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testItCreatesAShop(): void
    {
        // Arrange
        $managerId = $this->createManager('Amélie Poulain');

        // Act
        $this->createShop([
            'name' => 'Boutique Marais',
            'address' => '12 rue de Rivoli, Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'managerId' => $managerId,
        ]);

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame('Boutique Marais', $data['name']);
        self::assertSame('12 rue de Rivoli, Paris', $data['address']);
        self::assertSame(48.8566, $data['latitude']);
        self::assertSame(2.3522, $data['longitude']);
        self::assertSame($managerId, $data['managerId']);
        self::assertSame('open', $data['status']);
        self::assertTrue(Uuid::isValid($data['id']));
    }

    public function testItCreatesAClosedShopWhenStatusIsProvided(): void
    {
        // Arrange
        $managerId = $this->createManager('Gérant');

        // Act
        $this->createShop([
            'name' => 'Boutique fermée',
            'address' => 'Quelque part',
            'latitude' => 45.0,
            'longitude' => 5.0,
            'managerId' => $managerId,
            'status' => 'closed',
        ]);

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame('closed', $data['status']);
    }

    public function testItReturnsNotFoundWhenTheManagerIsUnknown(): void
    {
        // Act — a syntactically valid but non-existent manager id
        $this->createShop([
            'name' => 'Boutique orpheline',
            'address' => 'Nulle part',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'managerId' => (string) ManagerId::generate(),
        ]);

        // Assert
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(404, $data['status']);
    }

    public function testItRejectsCoordinatesOutOfRange(): void
    {
        // Arrange
        $managerId = $this->createManager('Gérant');

        // Act
        $this->createShop([
            'name' => 'Boutique',
            'address' => 'Adresse',
            'latitude' => 120.0,
            'longitude' => 200.0,
            'managerId' => $managerId,
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $messagesByPath = $this->violationMessages();
        self::assertSame('The latitude must be between -90 and 90.', $messagesByPath['latitude']);
        self::assertSame('The longitude must be between -180 and 180.', $messagesByPath['longitude']);
    }

    public function testItRejectsMissingRequiredFields(): void
    {
        // Act
        $this->createShop([]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        $messagesByPath = $this->violationMessages();
        self::assertSame('The shop name is required.', $messagesByPath['name']);
        self::assertSame('The shop address is required.', $messagesByPath['address']);
        self::assertSame('The latitude is required.', $messagesByPath['latitude']);
        self::assertSame('The longitude is required.', $messagesByPath['longitude']);
        self::assertSame('The manager id is required.', $messagesByPath['managerId']);
    }

    public function testItRejectsAManagerIdThatIsNotAUuid(): void
    {
        // Act
        $this->createShop([
            'name' => 'Boutique',
            'address' => 'Adresse',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'managerId' => 'not-a-uuid',
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        $messagesByPath = $this->violationMessages();
        self::assertSame('The manager id must be a valid UUID.', $messagesByPath['managerId']);
    }

    public function testItAcceptsANameAtTheMaximumLength(): void
    {
        // Arrange
        $managerId = $this->createManager('Gérant');

        // Act — 150 characters is the accepted boundary of the shop name
        $this->createShop([
            'name' => str_repeat('a', 150),
            'address' => 'Adresse',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'managerId' => $managerId,
        ]);

        // Assert
        self::assertResponseStatusCodeSame(201);
    }

    public function testItRejectsANameLongerThanTheMaximum(): void
    {
        // Act
        $this->createShop([
            'name' => str_repeat('a', 151),
            'address' => 'Adresse',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'managerId' => (string) ManagerId::generate(),
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        $messagesByPath = $this->violationMessages();
        self::assertSame('The shop name must not exceed 150 characters.', $messagesByPath['name']);
    }

    public function testItRejectsAnUnknownStatus(): void
    {
        // Arrange
        $managerId = $this->createManager('Gérant');

        // Act
        $this->createShop([
            'name' => 'Boutique',
            'address' => 'Adresse',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'managerId' => $managerId,
            'status' => 'paused',
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        $messagesByPath = $this->violationMessages();
        self::assertSame('The status must be one of: "open", "closed".', $messagesByPath['status']);
    }

    public function testItPrefersValidationOverNotFound(): void
    {
        // Act
        $this->createShop([
            'name' => 'Boutique',
            'address' => 'Adresse',
            'latitude' => 120.0,
            'longitude' => 2.0,
            'managerId' => (string) ManagerId::generate(),
        ]);

        // Assert
        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsMalformedJson(): void
    {
        // Act
        $this->client->request(
            'POST',
            '/api/shops',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{bad json',
        );

        // Assert
        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    private function createManager(string $name): string
    {
        $id = ManagerId::generate();
        $this->entityManager->persist(Manager::create($id, $name));
        $this->entityManager->flush();

        return (string) $id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createShop(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/shops',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, string>
     *
     * @throws \JsonException
     */
    private function violationMessages(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsArray($data['violations']);

        return array_column($data['violations'], 'message', 'propertyPath');
    }
}
