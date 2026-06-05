<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListCategories;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Repository\CategoryRepository;

final class ListCategories
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    /** @return CategoryTreeItem[] */
    public function execute(ListCategoriesQuery $query): array
    {
        $all = $this->categories->all($query->onlyActive);

        $roots = [];
        $byParent = [];

        foreach ($all as $cat) {
            if ($cat->parentId() === null) {
                $roots[$cat->id()] = $cat;
            } else {
                $byParent[$cat->parentId()][] = $cat;
            }
        }

        return array_values(array_map(
            fn (Category $root) => new CategoryTreeItem(
                root: $root,
                children: $byParent[$root->id()] ?? [],
            ),
            $roots,
        ));
    }
}
