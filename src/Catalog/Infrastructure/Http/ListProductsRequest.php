<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final class ListProductsRequest
{
    #[Assert\Positive(message: 'The page must be a positive integer.')]
    public int $page = 1;

    #[Assert\Range(notInRangeMessage: 'The limit must be between {{ min }} and {{ max }}.', min: 1, max: 100)]
    public int $limit = 20;

    #[Assert\Length(max: 255, maxMessage: 'The search term must not exceed {{ limit }} characters.')]
    public string $search = '';

    #[Assert\Choice(choices: ['name'], message: 'The sort field must be one of: {{ choices }}.')]
    public string $sort = 'name';

    #[Assert\Choice(choices: ['asc', 'desc'], message: 'The direction must be one of: {{ choices }}.')]
    public string $direction = 'asc';
}
