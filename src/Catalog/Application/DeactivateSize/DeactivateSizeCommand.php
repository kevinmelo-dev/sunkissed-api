<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateSize;

final readonly class DeactivateSizeCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
