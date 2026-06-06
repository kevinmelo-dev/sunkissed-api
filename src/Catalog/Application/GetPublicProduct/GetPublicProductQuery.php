<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetPublicProduct;

final readonly class GetPublicProductQuery
{
    public function __construct(
        public string $slug,
    ) {}
}
