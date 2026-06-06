<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListProductColorImages;

final readonly class ListProductColorImagesQuery
{
    public function __construct(
        public int $productId,
    ) {}
}
