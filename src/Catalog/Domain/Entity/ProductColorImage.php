<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

final class ProductColorImage
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $productId,
        private readonly int $colorId,
        private readonly string $storageKey,
        private int $position,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function colorId(): int
    {
        return $this->colorId;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function position(): int
    {
        return $this->position;
    }
}
