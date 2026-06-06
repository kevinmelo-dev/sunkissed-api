<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetPublicProduct;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Entity\Product;

final readonly class PublicProductDetail
{
    /**
     * @param  Category[]  $categories
     * @param  PublicColorEntry[]  $colors
     */
    public function __construct(
        public Product $product,
        public ?string $coverImageUrl,
        public array $categories,
        public array $colors,
    ) {}
}
