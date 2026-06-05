<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateColor;

final readonly class CreateColorCommand
{
    public function __construct(
        public string $name,
        public ?string $hex,
        public int $actorId,
    ) {}
}
