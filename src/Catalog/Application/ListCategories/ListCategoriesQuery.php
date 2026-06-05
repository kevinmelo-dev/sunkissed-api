<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListCategories;

final readonly class ListCategoriesQuery
{
    public function __construct(
        public bool $onlyActive = false,
    ) {}
}
