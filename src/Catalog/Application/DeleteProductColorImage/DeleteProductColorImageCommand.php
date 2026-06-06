<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeleteProductColorImage;

final readonly class DeleteProductColorImageCommand
{
    public function __construct(
        public int $imageId,
        public int $actorId,
    ) {}
}
