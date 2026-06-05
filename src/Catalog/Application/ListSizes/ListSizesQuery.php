<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListSizes;

final readonly class ListSizesQuery
{
    public function __construct(
        public bool $onlyActive = false,
    ) {}
}
