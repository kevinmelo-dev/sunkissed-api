<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListPublicProducts;

use Src\Catalog\Application\ProductCoverImage\ProductCoverImageResolver;
use Src\Catalog\Domain\Repository\ProductRepository;

final class ListPublicProducts
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductCoverImageResolver $coverResolver,
    ) {}

    /** @return PublicProductSummary[] */
    public function execute(ListPublicProductsQuery $query): array
    {
        $products = $this->products->all(onlyActive: true);

        $result = [];
        foreach ($products as $product) {
            $result[] = new PublicProductSummary(
                product: $product,
                coverImageUrl: $this->coverResolver->resolve($product),
            );
        }

        return $result;
    }
}
