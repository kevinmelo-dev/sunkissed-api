<?php

declare(strict_types=1);

namespace Src\Catalog\Application\SyncProductCategories;

final readonly class SyncProductCategoriesCommand
{
    public function __construct(
        public int $productId,
        /** @var int[] */
        public array $categoryIds,
        public int $actorId,
    ) {}
}
