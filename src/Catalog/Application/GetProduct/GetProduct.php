<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetProduct;

use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;

final class GetProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductVariantRepository $variants,
        private readonly ProductCategoryRepository $productCategories,
    ) {}

    public function execute(GetProductQuery $query): ProductDetail
    {
        $product = $this->products->find($query->id);

        if ($product === null) {
            throw new ProductNotFoundException($query->id);
        }

        return new ProductDetail(
            product: $product,
            variants: $this->variants->findForProduct($query->id),
            categories: $this->productCategories->categoriesForProduct($query->id),
        );
    }
}
