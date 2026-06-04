<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Enum;

enum ProductType: string
{
    case Kit = 'kit';
    case Single = 'single';
}
