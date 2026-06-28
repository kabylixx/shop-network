<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Http;

use App\Catalog\Infrastructure\Http\CreateProductRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateProductRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testItValidatesAValidPayload(): void
    {
        // Arrange
        $request = new CreateProductRequest();
        $request->name = 'Chaise en chêne';
        $request->pictureUrl = 'https://example.com/chaise.jpg';

        // Act
        $violations = $this->validator->validate($request);

        // Assert
        self::assertCount(0, $violations);
    }

    #[DataProvider('invalidPayloads')]
    public function testItRejectsInvalidPayload(?string $name, ?string $pictureUrl, string $expectedPath): void
    {
        // Arrange
        $request = new CreateProductRequest();
        $request->name = $name;
        $request->pictureUrl = $pictureUrl;

        // Act
        $violations = $this->validator->validate($request);

        // Assert
        self::assertGreaterThan(0, $violations->count());
        self::assertSame($expectedPath, $violations->get(0)->getPropertyPath());
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'blank name' => ['', 'https://example.com/p.jpg', 'name'];
        yield 'missing name' => [null, 'https://example.com/p.jpg', 'name'];
        yield 'name too long' => [str_repeat('a', 256), 'https://example.com/p.jpg', 'name'];
        yield 'blank picture url' => ['Table', '', 'pictureUrl'];
        yield 'invalid picture url' => ['Table', 'not-a-url', 'pictureUrl'];
        yield 'picture url without tld' => ['Table', 'http://localhost/p.jpg', 'pictureUrl'];
    }
}
