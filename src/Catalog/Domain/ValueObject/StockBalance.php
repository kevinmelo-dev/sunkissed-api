<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\ValueObject;

final readonly class StockBalance
{
    public function __construct(
        public int $variantId,
        public int $available,
    ) {}

    public function isAvailable(int $quantity): bool
    {
        return $this->available >= $quantity;
    }
}
