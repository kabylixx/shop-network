<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProductRequest
{
    #[Assert\NotBlank(message: 'The product name is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The product name must not exceed {{ limit }} characters.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'The picture URL is required.')]
    #[Assert\Length(max: 2000, maxMessage: 'The picture URL must not exceed {{ limit }} characters.')]
    #[Assert\Url(message: 'The picture URL must be a valid URL.', requireTld: true)]
    public ?string $pictureUrl = null;
}
