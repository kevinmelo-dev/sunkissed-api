<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListProducts;

final readonly class ListProductsQuery
{
    public function __construct(
        public bool $onlyActive = false,
    ) {}
}
