<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UploadProductColorImage;

final readonly class UploadProductColorImageCommand
{
    public function __construct(
        public int $productId,
        public int $colorId,
        public string $fileTempPath,
        public string $mimeType,
        public int $actorId,
    ) {}
}
