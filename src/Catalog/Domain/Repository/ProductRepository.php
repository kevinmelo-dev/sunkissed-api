<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Product;

interface ProductRepository
{
    public function find(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function save(Product $product): Product;
}
