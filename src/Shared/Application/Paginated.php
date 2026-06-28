<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * @template T
 */
final readonly class Paginated implements \JsonSerializable
{
    public int $totalPages;

    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
        $this->totalPages = $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }

    /**
     * @param list<T> $items
     *
     * @return self<T>
     */
    public static function fromPagination(array $items, int $total, Pagination $pagination): self
    {
        return new self($items, $total, $pagination->page, $pagination->limit);
    }

    /**
     * @return array{items: list<T>, page: int, limit: int, total: int, totalPages: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'totalPages' => $this->totalPages,
        ];
    }
}
