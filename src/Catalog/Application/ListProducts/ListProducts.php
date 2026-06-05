<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListProducts;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Repository\ProductRepository;

final class ListProducts
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    /** @return Product[] */
    public function execute(ListProductsQuery $query): array
    {
        return $this->products->all($query->onlyActive);
    }
}
