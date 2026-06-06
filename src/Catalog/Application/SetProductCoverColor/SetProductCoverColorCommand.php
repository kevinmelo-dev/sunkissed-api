<?php

declare(strict_types=1);

namespace Src\Catalog\Application\SetProductCoverColor;

final readonly class SetProductCoverColorCommand
{
    public function __construct(
        public int $productId,
        public int $colorId,
        public int $actorId,
    ) {}
}
