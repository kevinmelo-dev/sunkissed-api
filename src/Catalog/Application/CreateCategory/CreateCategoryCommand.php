<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateCategory;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $parentId,
        public int $actorId,
    ) {}
}
