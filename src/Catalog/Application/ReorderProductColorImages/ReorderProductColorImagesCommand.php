<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReorderProductColorImages;

final readonly class ReorderProductColorImagesCommand
{
    /**
     * @param  int[]  $orderedImageIds  Image ids in the desired display order.
     */
    public function __construct(
        public int $productId,
        public int $colorId,
        public array $orderedImageIds,
        public int $actorId,
    ) {}
}
