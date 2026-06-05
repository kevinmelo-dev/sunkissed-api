<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $type,
        public bool $active,
        public int $actorId,
    ) {}
}
