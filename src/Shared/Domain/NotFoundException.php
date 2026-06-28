<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Base class for domain exceptions that denote a missing referenced resource.
 *
 * The Problem Details listener maps any thrown {@see NotFoundException} to an
 * HTTP 404.
 */
abstract class NotFoundException extends \RuntimeException
{
}
