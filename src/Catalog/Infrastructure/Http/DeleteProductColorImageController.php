<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\DeleteProductColorImage\DeleteProductColorImage;
use Src\Catalog\Application\DeleteProductColorImage\DeleteProductColorImageCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class DeleteProductColorImageController
{
    public function __construct(
        private readonly DeleteProductColorImage $useCase,
    ) {}

    public function __invoke(Request $request, int $id, int $imageId): JsonResponse
    {
        $this->useCase->execute(new DeleteProductColorImageCommand(
            imageId: $imageId,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success(null);
    }
}
