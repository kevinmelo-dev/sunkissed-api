<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateColor;

final readonly class ReactivateColorCommand
{
    public function __construct(
        public int $id,
        public int $actorId,
    ) {}
}
