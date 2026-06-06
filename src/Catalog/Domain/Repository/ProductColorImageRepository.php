<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\ProductColorImage;

interface ProductColorImageRepository
{
    public function find(int $id): ?ProductColorImage;

    /** @return ProductColorImage[] ordered by position asc */
    public function listForProductColor(int $productId, int $colorId): array;

    /**
     * Returns all images for the product keyed by color_id.
     *
     * @return array<int, ProductColorImage[]>
     */
    public function listForProduct(int $productId): array;

    public function nextPosition(int $productId, int $colorId): int;

    public function save(ProductColorImage $image): ProductColorImage;

    public function delete(int $id): void;

    /** @param int[] $orderedIds */
    public function saveOrder(array $orderedIds): void;
}
