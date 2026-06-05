<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateProduct;

final readonly class DeactivateProductCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
