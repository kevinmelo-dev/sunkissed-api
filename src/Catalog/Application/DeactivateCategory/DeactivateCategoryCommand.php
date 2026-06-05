<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateCategory;

final readonly class DeactivateCategoryCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
