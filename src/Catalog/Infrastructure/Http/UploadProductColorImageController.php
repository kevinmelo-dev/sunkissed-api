<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Application\UploadProductColorImage\UploadProductColorImage;
use Src\Catalog\Application\UploadProductColorImage\UploadProductColorImageCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class UploadProductColorImageController
{
    public function __construct(
        private readonly UploadProductColorImage $useCase,
        private readonly ImageStorage $storage,
    ) {}

    public function __invoke(
        UploadProductColorImageRequest $request,
        int $id,
        int $colorId,
    ): JsonResponse {
        $file = $request->file('image');

        $image = $this->useCase->execute(new UploadProductColorImageCommand(
            productId: $id,
            colorId: $colorId,
            fileTempPath: $file->getRealPath(),
            mimeType: $file->getMimeType(),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $image->id(),
            'product_id' => $image->productId(),
            'color_id' => $image->colorId(),
            'position' => $image->position(),
            'url' => $this->storage->url($image->storageKey()),
        ], status: 201);
    }
}
