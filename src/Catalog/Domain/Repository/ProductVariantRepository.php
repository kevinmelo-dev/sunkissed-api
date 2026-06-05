<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\ValueObject\Sku;

interface ProductVariantRepository
{
    public function find(int $id): ?ProductVariant;

    public function findBySku(Sku $sku): ?ProductVariant;

    public function findCombination(int $productId, int $colorId, int $sizeId): ?ProductVariant;

    public function existsCombination(int $productId, int $colorId, int $sizeId): bool;

    /** @return ProductVariant[] All variants (active and inactive) for a product */
    public function findForProduct(int $productId): array;

    public function save(ProductVariant $variant): ProductVariant;
}
