<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListColors;

final readonly class ListColorsQuery
{
    public function __construct(
        public bool $onlyActive = false,
    ) {}
}
