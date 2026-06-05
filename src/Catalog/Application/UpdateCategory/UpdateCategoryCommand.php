<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateCategory;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?int $parentId,
        public int $actorId,
    ) {}
}
