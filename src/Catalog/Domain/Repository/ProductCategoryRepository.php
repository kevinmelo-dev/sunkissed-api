<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Category;

interface ProductCategoryRepository
{
    /** @return Category[] */
    public function categoriesForProduct(int $productId): array;

    /** @param int[] $categoryIds */
    public function sync(int $productId, array $categoryIds): void;
}
