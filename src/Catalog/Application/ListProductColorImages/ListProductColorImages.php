<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListProductColorImages;

use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Catalog\Domain\Repository\ProductRepository;

final class ListProductColorImages
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductColorImageRepository $images,
    ) {}

    /** @return ProductColorGroup[] */
    public function execute(ListProductColorImagesQuery $query): array
    {
        $product = $this->products->find($query->productId);

        if ($product === null) {
            throw new ProductNotFoundException($query->productId);
        }

        $grouped = $this->images->listForProduct($query->productId);

        $result = [];
        foreach ($grouped as $colorId => $images) {
            $result[] = new ProductColorGroup(colorId: $colorId, images: $images);
        }

        return $result;
    }
}
