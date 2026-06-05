<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateColor;

final readonly class UpdateColorCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $hex,
        public int $actorId,
    ) {}
}
