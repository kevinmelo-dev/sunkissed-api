<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetProduct;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Entity\ProductVariant;

final readonly class ProductDetail
{
    public function __construct(
        public Product $product,
        /** @var ProductVariant[] */
        public array $variants,
        /** @var Category[] */
        public array $categories,
    ) {}
}
