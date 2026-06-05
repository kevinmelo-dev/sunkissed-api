<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateColor;

final readonly class DeactivateColorCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
