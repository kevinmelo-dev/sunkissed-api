<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Service;

use Src\Catalog\Domain\Entity\ProductVariant;

final readonly class VariantCompositionResult
{
    public function __construct(
        /** @var ProductVariant[] Inactive variants whose combination is back in the desired set */
        public array $toReactivate,
        /** @var ProductVariant[] Active variants whose combination is no longer desired */
        public array $toDeactivate,
        /** @var array<int, array{colorId: int, sizeId: int}> New combinations that do not yet exist */
        public array $toCreate,
    ) {}
}
