<?php

declare(strict_types=1);

namespace App\Tests\Network\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class CreateManagerActionTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testItCreatesAManager(): void
    {
        // Act
        $this->createAManager(['name' => 'Amélie Poulain']);

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame('Amélie Poulain', $data['name']);
        self::assertTrue(Uuid::isValid($data['id']));
    }

    public function testItRejectsABlankName(): void
    {
        // Act
        $this->createAManager(['name' => '']);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame(422, $data['status']);
        self::assertIsArray($data['violations']);
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('The manager name is required.', $messagesByPath['name']);
    }

    public function testItAcceptsANameAtTheMaximumLength(): void
    {
        // Act — 150 characters is the accepted boundary of the manager name
        $this->createAManager(['name' => str_repeat('a', 150)]);

        // Assert
        self::assertResponseStatusCodeSame(201);
    }

    public function testItRejectsANameLongerThanTheMaximum(): void
    {
        // Act — one character past the boundary
        $this->createAManager(['name' => str_repeat('a', 151)]);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('The manager name must not exceed 150 characters.', $messagesByPath['name']);
    }

    public function testItRejectsMalformedJson(): void
    {
        // Act
        $this->client->request(
            'POST',
            '/api/managers',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{bad json',
        );

        // Assert
        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testItRejectsAWronglyTypedPayload(): void
    {
        // Act
        $this->createAManager(['name' => 123]);

        // Assert
        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createAManager(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/managers',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
