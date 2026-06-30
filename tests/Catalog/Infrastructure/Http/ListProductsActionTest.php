<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Http;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class ListProductsActionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testItListsWithDefaultParameters(): void
    {
        // Arrange
        $this->createProducts('Bravo', 'Alpha');

        // Act
        $data = $this->getProductsList('');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['page']);
        self::assertSame(20, $data['limit']);
        self::assertSame(2, $data['total']);
        self::assertSame(['Alpha', 'Bravo'], array_column($data['items'], 'name'));
    }

    public function testItPaginatesTheCatalogue(): void
    {
        // Arrange
        $this->createProducts('Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo');

        // Act
        $data = $this->getProductsList('page=2&limit=2&sort=name');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(5, $data['total']);
        self::assertSame(2, $data['page']);
        self::assertSame(2, $data['limit']);
        self::assertSame(3, $data['totalPages']);
        self::assertCount(2, $data['items']);
        self::assertSame(['Charlie', 'Delta'], array_column($data['items'], 'name'));
    }

    public function testItFiltersByNameCaseAndAccentInsensitive(): void
    {
        // Arrange
        $this->createProducts('Café', 'Thé', 'Eau', 'caFé au lait');

        // Act
        $data = $this->getProductsList('search=cafe');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(['Café', 'caFé au lait'], array_column($data['items'], 'name'));
    }

    public function testItEscapesLikeWildcardsInTheSearch(): void
    {
        // Arrange
        $this->createProducts('50% coton', '5089 coton');

        // Act
        $data = $this->getProductsList('search='.urlencode('50%'));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(1, $data['total']);
        self::assertSame(['50% coton'], array_column($data['items'], 'name'));
    }

    public function testItSortsByNameDescending(): void
    {
        // Arrange
        $this->createProducts('Avocat', 'Banane', 'Cerise');

        // Act
        $data = $this->getProductsList('sort=name&direction=desc');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(['Cerise', 'Banane', 'Avocat'], array_column($data['items'], 'name'));
    }

    public function testItSortsByNameAscending(): void
    {
        // Arrange
        $this->createProducts('Avocat', 'Banane', 'Cerise');

        // Act
        $data = $this->getProductsList('sort=name&direction=asc');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(['Avocat', 'Banane', 'Cerise'], array_column($data['items'], 'name'));
    }

    public function testItAcceptsASearchAtTheMaximumLength(): void
    {
        // Arrange
        $this->createProducts('Manteau Will');

        // Act — 255 characters is the accepted boundary of the search filter
        $data = $this->getProductsList('search='.str_repeat('a', 255));

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(0, $data['total']);
    }

    public function testItReturnsAnEmptyPageBeyondTheLastOne(): void
    {
        // Arrange
        $this->createProducts('Alpha', 'Bravo');

        // Act
        $data = $this->getProductsList('page=5&limit=10');

        // Assert
        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['total']);
        self::assertSame(1, $data['totalPages']);
        self::assertCount(0, $data['items']);
    }

    /**
     * @param array<string, string> $expectedViolationMessages
     *
     * @throws \JsonException
     */
    #[DataProvider('invalidQueryParameters')]
    public function testItRejectsInvalidQueryParameters(string $query, array $expectedViolationMessages): void
    {
        // Act
        $data = $this->getProductsList($query);

        // Assert
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame(422, $data['status']);
        $messagesByPath = array_column($data['violations'], 'message', 'propertyPath');
        foreach ($expectedViolationMessages as $path => $message) {
            self::assertSame($message, $messagesByPath[$path] ?? null);
        }
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function invalidQueryParameters(): iterable
    {
        yield 'page below 1' => ['page=0', ['page' => 'The page must be a positive integer.']];
        yield 'limit above cap' => ['limit=999', ['limit' => 'The limit must be between 1 and 100.']];
        yield 'unknown sort field' => ['sort=foo', ['sort' => 'The sort field must be one of: "name".']];
        yield 'unknown direction' => ['direction=sideways', ['direction' => 'The direction must be one of: "asc", "desc".']];
        yield 'search too long' => ['search='.str_repeat('a', 256), ['search' => 'The search term must not exceed 255 characters.']];
    }

    private function createProducts(string ...$names): void
    {
        foreach ($names as $name) {
            $this->entityManager->persist(
                Product::create(ProductId::generate(), $name, 'https://example.com/x.jpg'),
            );
        }
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function getProductsList(string $query): array
    {
        $this->client->request('GET', '/api/products?'.$query);

        $data = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($data);

        return $data;
    }
}
