<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReorderProductColorImages;

use Src\Catalog\Domain\Repository\ProductColorImageRepository;

final class ReorderProductColorImages
{
    public function __construct(
        private readonly ProductColorImageRepository $images,
    ) {}

    public function execute(ReorderProductColorImagesCommand $command): void
    {
        $this->images->saveOrder($command->orderedImageIds);
    }
}
