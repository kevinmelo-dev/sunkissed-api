<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateSize;

final readonly class CreateSizeCommand
{
    public function __construct(
        public string $name,
        public int $sortOrder,
        public int $actorId,
    ) {}
}
