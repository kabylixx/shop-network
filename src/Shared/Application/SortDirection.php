<?php

declare(strict_types=1);

namespace App\Shared\Application;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
