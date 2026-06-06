<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\ReorderProductColorImages\ReorderProductColorImages;
use Src\Catalog\Application\ReorderProductColorImages\ReorderProductColorImagesCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ReorderProductColorImagesController
{
    public function __construct(
        private readonly ReorderProductColorImages $useCase,
    ) {}

    public function __invoke(
        ReorderProductColorImagesRequest $request,
        int $id,
        int $colorId,
    ): JsonResponse {
        $this->useCase->execute(new ReorderProductColorImagesCommand(
            productId: $id,
            colorId: $colorId,
            orderedImageIds: $request->input('image_ids'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success(null);
    }
}
