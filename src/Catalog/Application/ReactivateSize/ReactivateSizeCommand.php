<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateSize;

final readonly class ReactivateSizeCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
