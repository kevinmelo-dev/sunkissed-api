<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetPublicProduct;

use Src\Catalog\Domain\Entity\Color;

final readonly class PublicColorEntry
{
    /**
     * @param  array<array{url: string, position: int, id: int}>  $images
     * @param  array<array{id: int, name: string, sort_order: int, variant_id: int, sku: string, price_cents: int, available: bool}>  $sizes
     */
    public function __construct(
        public Color $color,
        public array $images,
        public array $sizes,
    ) {}
}
