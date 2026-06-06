<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListProductColorImages;

use Src\Catalog\Domain\Entity\ProductColorImage;

final readonly class ProductColorGroup
{
    /**
     * @param  ProductColorImage[]  $images
     */
    public function __construct(
        public int $colorId,
        public array $images,
    ) {}
}
