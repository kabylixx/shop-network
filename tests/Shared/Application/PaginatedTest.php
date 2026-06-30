<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application;

use App\Shared\Application\Paginated;
use App\Shared\Application\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PaginatedTest extends TestCase
{
    #[DataProvider('totalPagesCases')]
    public function testItComputesTotalPages(int $total, int $limit, int $expected): void
    {
        // Act
        $paginated = new Paginated([], $total, 1, $limit);

        // Assert
        self::assertSame($expected, $paginated->totalPages);
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function totalPagesCases(): iterable
    {
        yield 'exact division' => [20, 10, 2];
        yield 'rounds up the remainder' => [21, 10, 3];
        yield 'single partial page' => [3, 10, 1];
        yield 'empty result' => [0, 10, 0];
    }

    public function testItExposesTheMetadataShapeForJson(): void
    {
        // Arrange
        $paginated = Paginated::fromPagination(['a', 'b'], 5, new Pagination(2, 2));

        // Act
        $json = $paginated->jsonSerialize();

        // Assert
        self::assertSame(
            ['items' => ['a', 'b'], 'page' => 2, 'limit' => 2, 'total' => 5, 'totalPages' => 3],
            $json,
        );
    }
}
