<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Infrastructure\Eloquent\CategoryModel;
use Src\Catalog\Infrastructure\Eloquent\ProductModel;

final class EloquentProductCategoryRepository implements ProductCategoryRepository
{
    public function categoriesForProduct(int $productId): array
    {
        return ProductModel::findOrFail($productId)
            ->categories()
            ->get()
            ->map(fn (CategoryModel $m) => $this->toCategory($m))
            ->all();
    }

    public function sync(int $productId, array $categoryIds): void
    {
        ProductModel::findOrFail($productId)
            ->categories()
            ->sync($categoryIds);
    }

    private function toCategory(CategoryModel $model): Category
    {
        return Category::reconstitute(
            id: $model->id,
            name: $model->name,
            slug: $model->slug,
            parentId: $model->parent_id,
            active: $model->active,
        );
    }
}
