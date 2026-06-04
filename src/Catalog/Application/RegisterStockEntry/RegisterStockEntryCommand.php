<?php

declare(strict_types=1);

namespace Src\Catalog\Application\RegisterStockEntry;

final readonly class RegisterStockEntryCommand
{
    public function __construct(
        public int $variantId,
        public int $quantity,
        public ?string $reason,
        public int $actorId,
    ) {}
}
