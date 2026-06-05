<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\ComposeProductVariants\ComposeProductVariants;
use Src\Catalog\Application\ComposeProductVariants\ComposeProductVariantsCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ComposeProductVariantsController
{
    public function __construct(
        private readonly ComposeProductVariants $useCase,
    ) {}

    public function __invoke(ComposeProductVariantsRequest $request, int $id): JsonResponse
    {
        $this->useCase->execute(new ComposeProductVariantsCommand(
            productId: $id,
            colorIds: $request->input('color_ids'),
            sizeIds: $request->input('size_ids'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success(null);
    }
}
