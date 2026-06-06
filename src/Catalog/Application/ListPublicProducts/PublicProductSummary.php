<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListPublicProducts;

use Src\Catalog\Domain\Entity\Product;

final readonly class PublicProductSummary
{
    public function __construct(
        public Product $product,
        public ?string $coverImageUrl,
    ) {}
}
