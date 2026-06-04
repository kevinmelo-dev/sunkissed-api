<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\ValueObject\Sku;

interface ProductVariantRepository
{
    public function find(int $id): ?ProductVariant;

    public function findBySku(Sku $sku): ?ProductVariant;

    public function existsCombination(int $productId, int $colorId, int $sizeId): bool;

    public function save(ProductVariant $variant): ProductVariant;
}
