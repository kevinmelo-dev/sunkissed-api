<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $type,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $actorId,
    ) {}
}
