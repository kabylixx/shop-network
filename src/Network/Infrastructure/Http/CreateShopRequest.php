<?php

declare(strict_types=1);

namespace App\Network\Infrastructure\Http;

use App\Network\Domain\Shop;
use App\Network\Domain\ShopStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateShopRequest
{
    #[Assert\NotBlank(message: 'The shop name is required.')]
    #[Assert\Length(max: Shop::NAME_MAX_LENGTH, maxMessage: 'The shop name must not exceed {{ limit }} characters.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'The shop address is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The shop address must not exceed {{ limit }} characters.')]
    public ?string $address = null;

    #[Assert\NotNull(message: 'The latitude is required.')]
    #[Assert\Range(notInRangeMessage: 'The latitude must be between {{ min }} and {{ max }}.', min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\NotNull(message: 'The longitude is required.')]
    #[Assert\Range(notInRangeMessage: 'The longitude must be between {{ min }} and {{ max }}.', min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\NotBlank(message: 'The manager id is required.')]
    #[Assert\Uuid(message: 'The manager id must be a valid UUID.')]
    public ?string $managerId = null;

    #[Assert\Choice(callback: [ShopStatus::class, 'values'], message: 'The status must be one of: {{ choices }}.')]
    public string $status = 'open';
}
