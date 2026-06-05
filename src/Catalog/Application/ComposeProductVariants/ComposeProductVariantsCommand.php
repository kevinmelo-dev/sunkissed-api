<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ComposeProductVariants;

final readonly class ComposeProductVariantsCommand
{
    public function __construct(
        public int $productId,
        /** @var int[] */
        public array $colorIds,
        /** @var int[] */
        public array $sizeIds,
        public int $actorId,
    ) {}
}
