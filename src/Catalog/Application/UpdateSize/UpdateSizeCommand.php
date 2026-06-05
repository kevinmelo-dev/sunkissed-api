<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateSize;

final readonly class UpdateSizeCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public int $sortOrder,
        public int $actorId,
    ) {}
}
