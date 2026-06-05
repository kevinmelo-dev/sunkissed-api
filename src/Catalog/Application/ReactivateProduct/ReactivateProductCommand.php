<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateProduct;

final readonly class ReactivateProductCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
