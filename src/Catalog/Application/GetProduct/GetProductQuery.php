<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetProduct;

final readonly class GetProductQuery
{
    public function __construct(
        public int $id,
    ) {}
}
