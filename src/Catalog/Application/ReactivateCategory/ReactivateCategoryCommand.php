<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateCategory;

final readonly class ReactivateCategoryCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
