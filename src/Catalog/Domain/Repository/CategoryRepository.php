<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Category;

interface CategoryRepository
{
    public function find(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    public function hasChildren(int $id): bool;

    /** @return Category[] */
    public function all(bool $onlyActive = false): array;

    public function save(Category $category): Category;
}
