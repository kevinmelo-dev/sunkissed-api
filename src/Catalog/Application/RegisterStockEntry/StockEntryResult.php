<?php

declare(strict_types=1);

namespace Src\Catalog\Application\RegisterStockEntry;

final readonly class StockEntryResult
{
    public function __construct(
        public int $movementId,
        public int $availableAfter,
    ) {}
}
