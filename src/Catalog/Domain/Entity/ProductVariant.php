<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\ValueObject\Money;

final class ProductVariant
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $productId,
        private readonly int $colorId,
        private readonly int $sizeId,
        private readonly Sku $sku,
        private Money $price,
        private bool $active,
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

    public function sizeId(): int
    {
        return $this->sizeId;
    }

    public function sku(): Sku
    {
        return $this->sku;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
