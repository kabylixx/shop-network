<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Http;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

#[Group('functional')]
final class CreateProductActionTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testItCreatesAProduct(): void
    {
        // Act
        $this->createAProduct(['name' => 'Chaise', 'pictureUrl' => 'https://example.com/chemise.jpg']);

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame('Chaise', $data['name']);
        self::assertSame('https://example.com/chemise.jpg', $data['pictureUrl']);
        self::assertTrue(Uuid::isValid($data['id'] ?? ''), 'Response carries a valid UUID id');
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        // Act
        $this->createAProduct(['name' => '', 'pictureUrl' => 'not-a-url']);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame(422, $data['status']);
        self::assertIsArray($data['violations']);
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        self::assertSame('The product name is required.', $messagesByPath['name']);
        self::assertSame('The picture URL must be a valid URL.', $messagesByPath['pictureUrl']);
        $codesByPath = array_column($data['violations'], 'code', 'propertyPath');
        self::assertSame('c1051bb4-d103-4f74-8988-acbcafc7fdc3', $codesByPath['name']);
        self::assertSame('57c2f299-1154-4870-89bb-ef3b1f5ad229', $codesByPath['pictureUrl']);
    }

    public function testItRejectsMalformedJson(): void
    {
        // Act
        $this->client->request(
            'POST',
            '/api/products',
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
        $this->createAProduct(['name' => 123, 'pictureUrl' => 'https://example.com/x.jpg']);

        // Assert
        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createAProduct(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
