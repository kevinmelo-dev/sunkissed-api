<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

final class Size
{
    public function __construct(
        private readonly ?int $id,
        private string $name,
        private int $sortOrder,
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

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
