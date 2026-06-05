<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Catalog\Infrastructure\Eloquent\CategoryModel;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function find(int $id): ?Category
    {
        $model = CategoryModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug): ?Category
    {
        $model = CategoryModel::where('slug', $slug)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = CategoryModel::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasChildren(int $id): bool
    {
        return CategoryModel::where('parent_id', $id)->exists();
    }

    public function all(bool $onlyActive = false): array
    {
        $query = CategoryModel::query();

        if ($onlyActive) {
            $query->where('active', true);
        }

        return $query->get()
            ->map(fn (CategoryModel $m) => $this->toEntity($m))
            ->all();
    }

    public function save(Category $category): Category
    {
        if ($category->id() !== null) {
            $model = CategoryModel::findOrFail($category->id());
        } else {
            $model = new CategoryModel;
        }

        $model->fill([
            'name' => $category->name(),
            'slug' => $category->slug(),
            'parent_id' => $category->parentId(),
            'active' => $category->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(CategoryModel $model): Category
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
