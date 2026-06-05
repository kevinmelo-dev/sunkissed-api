<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListCategories;

use Src\Catalog\Domain\Entity\Category;

final readonly class CategoryTreeItem
{
    /** @param Category[] $children */
    public function __construct(
        public Category $root,
        public array $children,
    ) {}
}
