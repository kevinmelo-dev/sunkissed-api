<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateProductVariant;

final readonly class UpdateProductVariantCommand
{
    public function __construct(
        public int $id,
        public ?int $priceCents,
        public ?string $image,
        public ?string $sku,
        public int $actorId,
    ) {}
}
