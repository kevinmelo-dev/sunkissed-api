<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\SyncProductCategories\SyncProductCategories;
use Src\Catalog\Application\SyncProductCategories\SyncProductCategoriesCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class SyncProductCategoriesController
{
    public function __construct(
        private readonly SyncProductCategories $useCase,
    ) {}

    public function __invoke(SyncProductCategoriesRequest $request, int $id): JsonResponse
    {
        $this->useCase->execute(new SyncProductCategoriesCommand(
            productId: $id,
            categoryIds: $request->input('category_ids', []),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success(null);
    }
}
