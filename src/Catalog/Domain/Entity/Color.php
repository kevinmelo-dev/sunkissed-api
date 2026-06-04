<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

final class Color
{
    public function __construct(
        private readonly ?int $id,
        private string $name,
        private ?string $hex,
        private bool $active,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function hex(): ?string
    {
        return $this->hex;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
